<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Enums\OrderPropertyName;

/**
 * Counts how often an order was put through the address check.
 *
 * Every failed attempt raises the counter, which OrderCheckService's circuit
 * breaker compares against the configured limit.
 *
 * A property value is always a string, also for a number.
 */
final class AttemptCounter
{
    public function __construct(
        private OrderPropertyReader $reader,
    ) {
    }

    public function next(int $orderId): string
    {
        $current = $this->reader->value(
            $orderId,
            OrderPropertyName::ATTEMPTS_COUNT
        );

        return (string) ((int) $current + 1);
    }
}
