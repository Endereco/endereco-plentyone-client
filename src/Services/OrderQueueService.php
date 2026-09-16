<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Enums\LogCode;
use EnderecoPlentyOneClient\Enums\OrderPropertyName;
use EnderecoPlentyOneClient\Enums\OrderTag;
use EnderecoPlentyOneClient\Enums\ProcessState;
use EnderecoPlentyOneClient\Enums\PropertyValue;

/**
 * Puts an order into the address validation queue and starts its check.
 *
 * Holds the order on its start status until a result exists, remembers the
 * previous status to restore later, and tags it pending.
 *
 * The queue row is written first, before anything on the order itself.
 * If that fails, nothing else happens. If a later write fails instead,
 * the row survives and the next batch drops it as stale, self-limiting.
 */
final class OrderQueueService
{
    /** The state a freshly queued order starts in. */
    private const MARKERS = [
        OrderPropertyName::PROCESS_STATE => ProcessState::ENQUEUED,
        OrderPropertyName::ATTEMPTS_COUNT => PropertyValue::ZERO,
    ];

    public function __construct(
        private StatusWriter $statusWriter,
        private StatusResolver $statusResolver,
        private OrderPropertyWriter $properties,
        private OrderProcessingState $processingState,
        private QueueIndexWriter $indexWriter,
        private QueueIndexReader $indexReader,
        private TagService $tags,
        private PluginLogger $logger,
    ) {
    }

    /**
     * Called by the OrderCreated listener. Refuses any state at all, not
     * just "enqueued" - a finished order must not be picked up again on
     * its own; re-checking one is only for a merchant, via enqueueFromFlow().
     */
    public function enqueueFromListener(int $orderId): void
    {
        if ($this->processingState->alreadyHandled($orderId)) {
            $this->logAlreadyKnown($orderId, LogCode::ORDER_ALREADY_HANDLED);

            return;
        }

        $this->enqueue($orderId, false);
    }

    /**
     * Called when a merchant's own flow asks for a check - unlike
     * enqueueFromListener(), also allowed for an order this plugin already
     * finished with. Still refused while a check is actually in flight.
     */
    public function enqueueFromFlow(int $orderId): void
    {
        if ($this->processingState->isInProgress($orderId)) {
            $this->logAlreadyKnown($orderId, LogCode::ORDER_ALREADY_QUEUED);

            return;
        }

        $this->enqueue($orderId, $this->processingState->alreadyHandled($orderId));
    }

    private function enqueue(int $orderId, bool $reenqueue): void
    {
        // Both callers already ruled out an in-progress order (via alreadyHandled()/
        // isInProgress()). Any existing row is a stale leftover from a failed removal
        // and would block add() for a re-enqueue.
        $this->indexWriter->remove($orderId);

        if (! $this->indexWriter->add($orderId, ProcessState::ENQUEUED)) {
            return;
        }

        $startStatusId = $this->statusResolver->resolveStart($orderId);
        $before = $this->statusWriter->writeStart($orderId, $startStatusId);

        if ($before === null) {
            return;
        }

        $markers = self::MARKERS;
        $markers[OrderPropertyName::STATUS_BEFORE] = $before;

        $written = $this->properties->write($orderId, $markers);

        if (! $written) {
            $this->statusWriter->revertStart($orderId, $before);

            return;
        }

        $this->tags->applyToOrder($orderId, [], [OrderTag::ADDRESS_VALIDATION_PENDING]);

        $this->logger->info(
            $reenqueue ? LogCode::ORDER_REENQUEUED : LogCode::ORDER_QUEUED,
            [
                'statusId' => $startStatusId,
                'queueSize' => $this->indexReader->count(ProcessState::ENQUEUED),
            ],
            $orderId,
        );
    }

    private function logAlreadyKnown(int $orderId, string $logCode): void
    {
        $this->logger->info(
            $logCode,
            ['state' => $this->processingState->stateOf($orderId)],
            $orderId
        );
    }
}
