<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Enums;

/**
 * How many attempts an unreachable service gets before the circuit breaker
 * gives up on an order. A fixed list, not a free number field.
 */
final class AttemptLimit
{
    /** A quarter of an hour at the cron's five-minute cadence. */
    public const THREE = '3';

    /** Half an hour. */
    public const SIX = '6';

    /** One hour. */
    public const TWELVE = '12';

    public const DEFAULT = self::THREE;

    public const ALL = [self::THREE, self::SIX, self::TWELVE];
}
