<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Dtos\AddressDto;
use EnderecoPlentyOneClient\Dtos\CheckResult;
use EnderecoPlentyOneClient\Enums\CommentKey;
use EnderecoPlentyOneClient\Enums\EnderecoRpc;
use EnderecoPlentyOneClient\Enums\LogCode;
use EnderecoPlentyOneClient\Enums\TargetStatusCase;
use Plenty\Modules\Comment\Contracts\CommentRepositoryContract;
use Plenty\Modules\Comment\Models\Comment;
use Plenty\Modules\User\Contracts\UserRepositoryContract;

/**
 * Writes what a completed check found into an order comment, so a result
 * is visible without opening the plugin's log or properties. One comment
 * per completed check - a re-check must not leave an outdated one as the
 * last word on the order.
 *
 * isVisibleForContact is always false: an internal note, not for the customer.
 */
final class OrderCommentWriter
{
    /** More than this makes the comment unreadable rather than helpful. */
    private const MAX_LISTED_PREDICTIONS = 5;

    /** Visually sets the original address apart from the correction/suggestion(s). */
    private const SEPARATOR = '---';

    public function __construct(
        private CommentRepositoryContract $comments,
        private UserRepositoryContract $users,
        private PluginSettings $settings,
        private PluginTranslator $translator,
        private AddressIssueReasons $issueReasons,
        private PluginLogger $logger,
        private SubdivisionResolver $subdivisions,
    ) {
    }

    /** @param array{0: bool, 1: bool}|null $packstationFlags */
    public function write(
        int $orderId,
        string $case,
        CheckResult $result,
        ?array $packstationFlags,
        AddressDto $original
    ): bool {
        return $this->persist($orderId, $this->text($case, $result, $packstationFlags, $original));
    }

    public function writeGaveUp(int $orderId): bool
    {
        return $this->persist($orderId, $this->trans(CommentKey::GAVE_UP));
    }

    private function persist(int $orderId, string $text): bool
    {
        $userId = $this->settings->commentUserId();

        if (! $this->userExists($userId)) {
            $this->logger->error(
                LogCode::COMMENT_USER_MISSING,
                ['userId' => $userId],
                $orderId
            );

            return false;
        }

        try {
            $this->comments->createComment([
                'referenceType' => Comment::REFERENCE_TYPE_ORDER,
                'referenceValue' => $orderId,
                'text' => $text,
                'isVisibleForContact' => false,
                'userId' => $userId,
            ]);
        } catch (\Throwable $exception) {
            $this->logger->exception(
                LogCode::COMMENT_FAILED,
                $exception,
                [],
                $orderId
            );

            return false;
        }

        $this->logger->info(LogCode::COMMENT_WRITTEN, [], $orderId);

        return true;
    }

    /**
     * A non-positive id is rejected outright, before getUserById() ever
     * sees it - we can't rely on what PlentyONE does with one, now or in
     * the future.
     */
    private function userExists(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        try {
            $this->users->getUserById($userId);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * "<br>" not "\n" - the backend renders the comment as HTML.
     *
     * @param array{0: bool, 1: bool}|null $packstationFlags
     */
    private function text(
        string $case,
        CheckResult $result,
        ?array $packstationFlags,
        AddressDto $original
    ): string {
        $lines = [$this->headerText($case)];

        if ($packstationFlags !== null) {
            $lines[] = $this->trans($packstationFlags[0] ? CommentKey::PACKSTATION : CommentKey::POSTFILIALE);
        }

        if ($case !== TargetStatusCase::CORRECT) {
            array_push($lines, ...$this->detailLines($case, $result, $original));
        }

        return implode('<br>', $lines);
    }

    /** Only CORRECT skips detail lines - every other case still needs its own header text. */
    private function headerText(string $case): string
    {
        return match ($case) {
            TargetStatusCase::CORRECT => $this->trans(CommentKey::CORRECT),
            TargetStatusCase::MINOR_CORRECTION => $this->trans(CommentKey::MINOR_CORRECTION),
            default => $this->trans(CommentKey::HEADER),
        };
    }

    /** @return list<string> */
    private function detailLines(string $case, CheckResult $result, AddressDto $original): array
    {
        $lines = [];

        $suggestion = $result->suggestion();
        $predictions = $result->allPredictions();

        [$showAddress3, $showSubdivision] = $this->shownFields($original, $predictions);
        $originalFields = $this->addressFields($original);

        if ($suggestion !== null) {
            array_push($lines, ...$this->originalAddressLines($originalFields, $showAddress3, $showSubdivision));
            $lines[] = $this->trans(
                $case === TargetStatusCase::MINOR_CORRECTION ? CommentKey::CORRECTION : CommentKey::SUGGESTION
            );
            array_push($lines, ...$this->addressLines(
                $this->addressFields($suggestion),
                $showAddress3,
                $showSubdivision,
                $originalFields
            ));
        } elseif ($predictions !== []) {
            array_push($lines, ...$this->originalAddressLines($originalFields, $showAddress3, $showSubdivision));

            // Several candidates: suggestion() is null, list some instead.
            foreach (array_slice($predictions, 0, self::MAX_LISTED_PREDICTIONS) as $index => $prediction) {
                if ($index > 0) {
                    $lines[] = self::SEPARATOR;
                }

                $lines[] = $this->trans(
                    CommentKey::SUGGESTION_NUMBERED,
                    ['number' => (string) ($index + 1)]
                );
                array_push($lines, ...$this->addressLines(
                    $this->predictionFields($prediction, $original->countryId()),
                    $showAddress3,
                    $showSubdivision,
                    $originalFields
                ));
            }
        } else {
            // No suggestion and no predictions at all - CheckFinding::NOT_FOUND.
            $lines[] = $this->trans(CommentKey::NO_SUGGESTION);

            $reason = $this->issueReasons->resolve($result);

            if ($reason !== null) {
                [$key, $replace] = $reason;
                $lines[] = $this->trans($key, $replace);
            }
        }

        return $lines;
    }

    /** @param array<string, string> $replace */
    private function trans(string $key, array $replace = []): string
    {
        return $this->translator->trans($key, $replace);
    }

    /**
     * Always shown first, framed by SEPARATOR on both sides, so a
     * correction/suggestion right after it reads as the comparison it is.
     *
     * @param array{
     *     address1: string, address2: string, postalCode: string,
     *     town: string, address3: string, subdivisionName: string
     * } $originalFields
     * @return list<string>
     */
    private function originalAddressLines(array $originalFields, bool $showAddress3, bool $showSubdivision): array
    {
        return [
            self::SEPARATOR,
            $this->trans(CommentKey::ORIGINAL_ADDRESS),
            ...$this->addressLines($originalFields, $showAddress3, $showSubdivision),
            self::SEPARATOR,
        ];
    }

    /**
     * The six fields addressLines() renders for an address we already have
     * as an AddressDto - the original or the suggestion, both carrying a
     * resolved Plenty state id rather than a raw subdivision code.
     *
     * @return array{
     *     address1: string, address2: string, postalCode: string,
     *     town: string, address3: string, subdivisionName: string
     * }
     */
    private function addressFields(AddressDto $address): array
    {
        return [
            'address1' => $address->address1(),
            'address2' => $address->address2(),
            'postalCode' => $address->postalCode(),
            'town' => $address->town(),
            'address3' => $address->address3(),
            'subdivisionName' => $this->subdivisions->plentyToName($address->countryId(), $address->stateId()),
        ];
    }

    /**
     * The six fields addressLines() renders for a raw prediction - the only
     * source that still carries a subdivision as an ISO code rather than a
     * resolved Plenty state id.
     *
     * @param array<string, string> $prediction
     * @return array{
     *     address1: string, address2: string, postalCode: string,
     *     town: string, address3: string, subdivisionName: string
     * }
     */
    private function predictionFields(array $prediction, int $countryId): array
    {
        return [
            'address1' => $prediction[EnderecoRpc::FIELD_STREET] ?? '',
            'address2' => $prediction[EnderecoRpc::FIELD_HOUSE_NUMBER] ?? '',
            'postalCode' => $prediction[EnderecoRpc::FIELD_POST_CODE] ?? '',
            'town' => $prediction[EnderecoRpc::FIELD_CITY_NAME] ?? '',
            'address3' => $prediction[EnderecoRpc::FIELD_ADDITIONAL_INFO] ?? '',
            'subdivisionName' => $this->subdivisions->isoToName(
                $countryId,
                $prediction[EnderecoRpc::FIELD_SUBDIVISION_CODE] ?? ''
            ),
        ];
    }

    /**
     * One line per field, "label: value". Street, house number, postal
     * code and town always; address3 and the subdivision name only when
     * shownFields() decided they're worth showing - address4 and country
     * are never shown, a check never corrects either. A field that differs
     * from $highlightAgainst is marked up as changed - null (the original's
     * own block) means nothing is ever highlighted.
     *
     * @param array{
     *     address1: string, address2: string, postalCode: string,
     *     town: string, address3: string, subdivisionName: string
     * } $fields
     * @param array{
     *     address1: string, address2: string, postalCode: string,
     *     town: string, address3: string, subdivisionName: string
     * }|null $highlightAgainst
     * @return list<string>
     */
    private function addressLines(
        array $fields,
        bool $showAddress3,
        bool $showSubdivision,
        ?array $highlightAgainst = null
    ): array {
        $lines = [
            $this->fieldLine(CommentKey::STREET, $fields['address1'], $highlightAgainst['address1'] ?? null),
            $this->fieldLine(CommentKey::HOUSE_NUMBER, $fields['address2'], $highlightAgainst['address2'] ?? null),
        ];

        if ($showAddress3) {
            $lines[] = $this->fieldLine(
                CommentKey::ADDITIONAL_INFO,
                $fields['address3'],
                $highlightAgainst['address3'] ?? null
            );
        }

        $lines[] = $this->fieldLine(
            CommentKey::POSTAL_CODE,
            $fields['postalCode'],
            $highlightAgainst['postalCode'] ?? null
        );
        $lines[] = $this->fieldLine(CommentKey::TOWN, $fields['town'], $highlightAgainst['town'] ?? null);

        if ($showSubdivision) {
            $lines[] = $this->fieldLine(
                CommentKey::SUBDIVISION,
                $fields['subdivisionName'],
                $highlightAgainst['subdivisionName'] ?? null
            );
        }

        return $lines;
    }

    /**
     * The label translations already carry their own trailing ":" - every
     * field line needs one. An empty value is spelled out, not left blank.
     *
     * $originalValue is the original's value for this same field, to
     * compare against - null skips the comparison entirely, so the
     * original's own block (compared against itself) is never highlighted.
     */
    private function fieldLine(string $labelKey, string $value, ?string $originalValue = null): string
    {
        $display = $value !== '' ? $value : $this->trans(CommentKey::EMPTY_VALUE);

        if ($originalValue !== null && $value !== $originalValue) {
            $display = '<b><u>' . $display . '</u></b>';
        }

        return $this->trans($labelKey) . ' ' . $display;
    }

    /**
     * Whether address3/subdivision appear at all - decided once per
     * comment, the same for every address shown in it.
     *
     * @param list<array<string, string>> $predictions
     * @return array{0: bool, 1: bool} address3, subdivision
     */
    private function shownFields(AddressDto $original, array $predictions): array
    {
        return [
            $this->fieldShown($predictions, EnderecoRpc::FIELD_ADDITIONAL_INFO, $original->address3() !== ''),
            $this->fieldShown($predictions, EnderecoRpc::FIELD_SUBDIVISION_CODE, $original->stateId() !== null),
        ];
    }

    /**
     * False when the answer never mentions the field, or when neither the
     * original nor any of the first five candidates has a value for it.
     *
     * @param list<array<string, string>> $predictions
     */
    private function fieldShown(array $predictions, string $key, bool $originalHasValue): bool
    {
        $present = false;

        foreach ($predictions as $prediction) {
            $present = $present || array_key_exists($key, $prediction);
        }

        if (! $present) {
            return false;
        }

        if ($originalHasValue) {
            return true;
        }

        foreach (array_slice($predictions, 0, self::MAX_LISTED_PREDICTIONS) as $prediction) {
            if (($prediction[$key] ?? '') !== '') {
                return true;
            }
        }

        return false;
    }
}
