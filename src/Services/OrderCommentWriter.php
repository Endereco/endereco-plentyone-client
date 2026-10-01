<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Dtos\AddressDto;
use EnderecoPlentyOneClient\Dtos\CheckResult;
use EnderecoPlentyOneClient\Enums\CommentKey;
use EnderecoPlentyOneClient\Enums\EnderecoRpc;
use EnderecoPlentyOneClient\Enums\Language;
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
    /** One line, no further detail needed - see text(). */
    private const ONE_LINE_CASES = [
        TargetStatusCase::CORRECT => CommentKey::CORRECT,
        TargetStatusCase::MINOR_CORRECTION => CommentKey::MINOR_CORRECTION,
    ];

    /** More than this makes the comment unreadable rather than helpful. */
    private const MAX_LISTED_PREDICTIONS = 5;

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
        $isOneLineCase = isset(self::ONE_LINE_CASES[$case]);

        $lines = [
            $isOneLineCase ? $this->trans(self::ONE_LINE_CASES[$case]) : $this->trans(CommentKey::HEADER),
        ];

        if ($packstationFlags !== null) {
            $lines[] = $this->trans($packstationFlags[0] ? CommentKey::PACKSTATION : CommentKey::POSTFILIALE);
        }

        if (! $isOneLineCase) {
            array_push($lines, ...$this->detailLines($result, $original));
        }

        return implode('<br>', $lines);
    }

    /** @return list<string> */
    private function detailLines(CheckResult $result, AddressDto $original): array
    {
        $lines = [];

        $suggestion = $result->suggestion();
        $predictions = $result->allPredictions();

        [$showAddress3, $showSubdivision] = $this->shownFields($original, $predictions);

        if ($suggestion !== null) {
            $lines[] = $this->trans(CommentKey::SUGGESTION);
            array_push($lines, ...$this->addressLines(
                $suggestion->address1(),
                $suggestion->address2(),
                $suggestion->postalCode(),
                $suggestion->town(),
                $suggestion->address3(),
                $this->subdivisions->plentyToName($suggestion->countryId(), $suggestion->stateId()),
                $showAddress3,
                $showSubdivision
            ));
        } elseif ($predictions !== []) {
            // Several candidates: suggestion() is null, list some instead.
            foreach (array_slice($predictions, 0, self::MAX_LISTED_PREDICTIONS) as $index => $prediction) {
                $lines[] = $this->trans(
                    CommentKey::SUGGESTION_NUMBERED,
                    ['number' => (string) ($index + 1)]
                );
                array_push($lines, ...$this->addressLines(
                    $prediction[EnderecoRpc::FIELD_STREET] ?? '',
                    $prediction[EnderecoRpc::FIELD_HOUSE_NUMBER] ?? '',
                    $prediction[EnderecoRpc::FIELD_POST_CODE] ?? '',
                    $prediction[EnderecoRpc::FIELD_CITY_NAME] ?? '',
                    $prediction[EnderecoRpc::FIELD_ADDITIONAL_INFO] ?? '',
                    $this->subdivisions->isoToName(
                        $original->countryId(),
                        $prediction[EnderecoRpc::FIELD_SUBDIVISION_CODE] ?? ''
                    ),
                    $showAddress3,
                    $showSubdivision
                ));
                $lines[] = '';
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
        return $this->translator->trans($key, $replace, Language::GERMAN);
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

    /**
     * One line per field, "label: value". Street, house number, postal
     * code and town always; address3 and the subdivision name only when
     * shownFields() decided they're worth showing - address4 and country
     * are never shown, a check never corrects either.
     *
     * @return list<string>
     */
    private function addressLines(
        string $address1,
        string $address2,
        string $postalCode,
        string $town,
        string $address3,
        string $subdivisionName,
        bool $showAddress3,
        bool $showSubdivision
    ): array {
        $lines = [
            $this->fieldLine(CommentKey::STREET, $address1),
            $this->fieldLine(CommentKey::HOUSE_NUMBER, $address2),
        ];

        if ($showAddress3) {
            $lines[] = $this->fieldLine(CommentKey::ADDITIONAL_INFO, $address3);
        }

        $lines[] = $this->fieldLine(CommentKey::POSTAL_CODE, $postalCode);
        $lines[] = $this->fieldLine(CommentKey::TOWN, $town);

        if ($showSubdivision) {
            $lines[] = $this->fieldLine(CommentKey::SUBDIVISION, $subdivisionName);
        }

        return $lines;
    }

    /**
     * The label translations already carry their own trailing ":" - every
     * field line needs one. An empty value is spelled out, not left blank.
     */
    private function fieldLine(string $labelKey, string $value): string
    {
        $display = $value !== '' ? $value : $this->trans(CommentKey::EMPTY_VALUE);

        return $this->trans($labelKey) . ' ' . $display;
    }
}
