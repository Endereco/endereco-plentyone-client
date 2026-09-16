<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Enums;

/**
 * The state of the address after the check - as opposed to CheckFinding,
 * which is the service's raw answer. A single, comparable value on purpose:
 * what a merchant filters on when building an automation in plentyFlow.
 */
final class CheckOutcome
{
    /** The address was already correct. */
    public const CORRECT = 'correct';

    /** The address was repaired and the repair was written to the order. */
    public const CORRECTED = 'corrected';

    /** Not repairable, or a repair exists but was not applied automatically. */
    public const ISSUE = 'issue';

    /** The service could not be reached and no attempt is left. */
    public const FAILED = 'failed';
}
