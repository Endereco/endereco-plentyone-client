<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Dtos\CheckResult;
use EnderecoPlentyOneClient\Enums\CheckFinding;
use EnderecoPlentyOneClient\Enums\CheckOutcome;

/**
 * Combines the service's finding with whether a correction was applied
 * into what actually happened to the order.
 *
 * A correctable address becomes "corrected" when applied, "issue" when not
 * - neither is knowable from the service's answer alone, which is why this
 * lives here, not in the mapper.
 */
final class OutcomeResolver
{
    public function resolve(CheckResult $result, bool $applied): string
    {
        if ($result->finding() === CheckFinding::CORRECT) {
            return CheckOutcome::CORRECT;
        }

        if ($applied) {
            return CheckOutcome::CORRECTED;
        }

        return CheckOutcome::ISSUE;
    }
}
