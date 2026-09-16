<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Dtos\CheckResult;
use EnderecoPlentyOneClient\Enums\CommentKey;
use EnderecoPlentyOneClient\Enums\EnderecoStatus;

/**
 * Why endereco could not offer a suggestion at all (CheckFinding::NOT_FOUND)
 * - the missing/invalid house number, or, for a Packstation/Postfiliale, one
 * of its two numbers. OrderCommentWriter appends whatever reason is found
 * here as extra lines.
 *
 * ADDITIONAL_INFO_NOT_FOUND is the only status code for a Packstation/
 * Postfiliale case reaching here, in every combination of "location number
 * missing" and "personal number missing or wrong" - it cannot say which, so
 * POST_NUMBER_ISSUE names both possibilities.
 *
 */
final class AddressIssueReasons
{
    /** @return array{0: string, 1: array<string, string>}|null */
    public function resolve(CheckResult $result): ?array
    {
        $codes = $result->statusCodes();
        $type = $this->packstationOrPostfilialeType($codes);

        if ($type !== null) {
            return $this->postNumberReason($codes, $type);
        }

        return $this->buildingNumberReason($codes);
    }

    /** @param list<string> $codes */
    private function packstationOrPostfilialeType(array $codes): ?string
    {
        if (in_array(EnderecoStatus::ADDRESS_IS_PACKSTATION, $codes, true)) {
            return 'Packstation';
        }

        if (in_array(EnderecoStatus::ADDRESS_IS_POSTOFFICE, $codes, true)) {
            return 'Postfiliale';
        }

        return null;
    }

    /**
     * @param list<string> $codes
     * @return array{0: string, 1: array<string, string>}|null
     */
    private function postNumberReason(array $codes, string $type): ?array
    {
        if (! in_array(EnderecoStatus::ADDITIONAL_INFO_NOT_FOUND, $codes, true)) {
            return null;
        }

        return [CommentKey::POST_NUMBER_ISSUE, ['type' => $type]];
    }

    /**
     * @param list<string> $codes
     * @return array{0: string, 1: array<string, string>}|null
     */
    private function buildingNumberReason(array $codes): ?array
    {
        if (in_array(EnderecoStatus::BUILDING_NUMBER_IS_MISSING, $codes, true)) {
            return [CommentKey::BUILDING_NUMBER_MISSING, []];
        }

        if (in_array(EnderecoStatus::BUILDING_NUMBER_NOT_FOUND, $codes, true)) {
            return [CommentKey::BUILDING_NUMBER_NOT_FOUND, []];
        }

        return null;
    }
}
