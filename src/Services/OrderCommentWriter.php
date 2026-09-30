<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

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
    ) {
    }

    /** @param array{0: bool, 1: bool}|null $packstationFlags */
    public function write(int $orderId, string $case, CheckResult $result, ?array $packstationFlags): bool
    {
        return $this->persist($orderId, $this->text($case, $result, $packstationFlags));
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
    private function text(string $case, CheckResult $result, ?array $packstationFlags): string
    {
        $isOneLineCase = isset(self::ONE_LINE_CASES[$case]);

        $lines = [
            $isOneLineCase ? $this->trans(self::ONE_LINE_CASES[$case]) : $this->trans(CommentKey::HEADER),
        ];

        if ($packstationFlags !== null) {
            $lines[] = $this->trans($packstationFlags[0] ? CommentKey::PACKSTATION : CommentKey::POSTFILIALE);
        }

        if (! $isOneLineCase) {
            array_push($lines, ...$this->detailLines($result));
        }

        return implode('<br>', $lines);
    }

    /** @return list<string> */
    private function detailLines(CheckResult $result): array
    {
        $lines = [];

        $suggestion = $result->suggestion();
        $predictions = $result->allPredictions();

        if ($suggestion !== null) {
            $lines[] = $this->trans(CommentKey::SUGGESTION);
            array_push($lines, ...$this->addressLines(
                $suggestion->address1(),
                $suggestion->address2(),
                $suggestion->postalCode(),
                $suggestion->town(),
                $suggestion->address3()
            ));
        } elseif ($predictions !== []) {
            // Several candidates: suggestion() is null, list some instead.
            foreach (array_slice($predictions, 0, self::MAX_LISTED_PREDICTIONS) as $index => $prediction) {
                $lines[] = $this->trans(
                    CommentKey::SUGGESTION_NUMBERED,
                    ['number' => (string) ($index + 1)]
                );
                array_push($lines, ...$this->format($prediction));
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
     * @param array<string, string> $prediction
     * @return list<string>
     */
    private function format(array $prediction): array
    {
        return $this->addressLines(
            $prediction[EnderecoRpc::FIELD_STREET] ?? '',
            $prediction[EnderecoRpc::FIELD_HOUSE_NUMBER] ?? '',
            $prediction[EnderecoRpc::FIELD_POST_CODE] ?? '',
            $prediction[EnderecoRpc::FIELD_CITY_NAME] ?? '',
            $prediction[EnderecoRpc::FIELD_ADDITIONAL_INFO] ?? ''
        );
    }

    /**
     * Street+number on one line, postcode+town on the next, address3 on its
     * own line and only when not empty. address4 is deliberately never
     * shown here - a comment only lists what a check could ever correct,
     * and address4 never is.
     *
     * @return list<string>
     */
    private function addressLines(
        string $address1,
        string $address2,
        string $postalCode,
        string $town,
        string $address3
    ): array {
        $lines = [
            trim($address1 . ' ' . $address2),
            trim($postalCode . ' ' . $town),
        ];

        if ($address3 !== '') {
            $lines[] = $address3;
        }

        return $lines;
    }
}
