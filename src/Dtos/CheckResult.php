<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Dtos;

/**
 * What came back from the address check.
 *
 * suggestion() is null when the API answered with no prediction or several;
 * allPredictions() then still carries every raw candidate, for a comment to
 * list them.
 */
final class CheckResult
{
    /**
     * @param list<string> $statusCodes
     * @param list<array<string, string>> $allPredictions
     */
    public function __construct(
        private string $finding,
        private ?AddressDto $suggestion,
        private array $statusCodes,
        private array $allPredictions = [],
    ) {
    }

    /** One of the CheckFinding values. */
    public function finding(): string
    {
        return $this->finding;
    }

    public function suggestion(): ?AddressDto
    {
        return $this->suggestion;
    }

    /** @return list<string> */
    public function statusCodes(): array
    {
        return $this->statusCodes;
    }

    /** All status codes of the answer, only used for the log. */
    public function statusCodesAsText(): string
    {
        return implode(', ', $this->statusCodes);
    }

    /** @return list<array<string, string>> */
    public function allPredictions(): array
    {
        return $this->allPredictions;
    }
}
