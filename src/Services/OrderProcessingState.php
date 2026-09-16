<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Enums\OrderPropertyName;
use EnderecoPlentyOneClient\Enums\ProcessState;

/**
 * Whether this plugin has ever touched an order's address, and whether a
 * check is currently in flight. Null means never touched.
 *
 * alreadyHandled() blocks any repeat automatic action; isInProgress() is
 * the narrower check for enqueueFromFlow(), which may revisit a finished order.
 */
final class OrderProcessingState
{
    public function __construct(
        private OrderPropertyReader $reader,
    ) {
    }

    /** Null when the order has never been queued by this plugin, or is gone. */
    public function stateOf(int $orderId): ?string
    {
        try {
            return $this->reader->value($orderId, OrderPropertyName::PROCESS_STATE);
        } catch (\Throwable) {
            return null;
        }
    }

    public function alreadyHandled(int $orderId): bool
    {
        return $this->stateOf($orderId) !== null;
    }

    /** True while a check is actually in flight - too early to touch again. */
    public function isInProgress(int $orderId): bool
    {
        return in_array(
            $this->stateOf($orderId),
            [ProcessState::ENQUEUED, ProcessState::CHECKED],
            true
        );
    }
}
