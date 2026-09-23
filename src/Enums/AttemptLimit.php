<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Enums;

/**
 * How many attempts an unreachable service gets before the circuit breaker
 * gives up on an order. Not configurable.
 */
final class AttemptLimit
{
    /** A quarter of an hour at the cron's five-minute cadence. */
    public const MAX_ATTEMPTS = 3;
}
