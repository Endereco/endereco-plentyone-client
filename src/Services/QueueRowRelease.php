<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Enums\OrderPropertyName;
use EnderecoPlentyOneClient\Enums\OrderTag;
use EnderecoPlentyOneClient\Enums\ProcessState;

/**
 * Abandons a queue row with no result to report at all - not a check that
 * succeeded, and not one the circuit breaker gave up on (see CheckResultWriter
 * for both). For a row whose order no longer meets the conditions for a
 * check, or one a DB write left stuck.
 *
 * Besides removing the row: takes off the pending tag, and tries to restore
 * the order's status - a no-op, same as today, if the order already left
 * its start status or the configured start status has since changed.
 */
final class QueueRowRelease
{
    public function __construct(
        private QueueIndexWriter $indexWriter,
        private OrderPropertyWriter $properties,
        private TagService $tags,
        private StatusWriter $statusWriter,
    ) {
    }

    public function release(int $orderId): void
    {
        $this->properties->write($orderId, [
            OrderPropertyName::PROCESS_STATE => ProcessState::FINISHED,
        ]);
        $this->indexWriter->remove($orderId);
        $this->tags->applyToOrder($orderId, [OrderTag::ADDRESS_VALIDATION_PENDING], []);
        $this->statusWriter->writeTarget($orderId);
    }
}
