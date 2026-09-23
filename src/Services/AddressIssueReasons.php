<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Dtos\CheckResult;
use EnderecoPlentyOneClient\Enums\CommentKey;
use EnderecoPlentyOneClient\Enums\EnderecoStatus;

/**
 * Why endereco could not offer a suggestion at all (CheckFinding::NOT_FOUND)
 * - the missing/invalid house number. OrderCommentWriter appends the reason
 * found here as an extra line.
 */
final class AddressIssueReasons
{
    /** @return array{0: string, 1: array<string, string>}|null */
    public function resolve(CheckResult $result): ?array
    {
        $codes = $result->statusCodes();

        if (in_array(EnderecoStatus::BUILDING_NUMBER_IS_MISSING, $codes, true)) {
            return [CommentKey::BUILDING_NUMBER_MISSING, []];
        }

        if (in_array(EnderecoStatus::BUILDING_NUMBER_NOT_FOUND, $codes, true)) {
            return [CommentKey::BUILDING_NUMBER_NOT_FOUND, []];
        }

        return null;
    }
}
