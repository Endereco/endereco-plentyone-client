<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Enums\LogCode;
use EnderecoPlentyOneClient\Enums\ProcessState;

/**
 * Cleans up queue rows that should not be sitting in the table any more:
 * rows too old regardless of state, and any row whose order already
 * finished (or is gone) - regardless of what state the row itself still
 * shows, since nothing revisits a row once its order is done.
 */
final class QueueSweepService
{
    /** Upper bound so one run cannot run unbounded. */
    private const MAX_ROWS = 500;

    /**
     * 7 days, not configurable: this is DB hygiene, not a merchant-facing setting -
     * under normal load the queue is worked off in minutes.
     */
    private const MAX_AGE_SECONDS = 604800;

    public function __construct(
        private QueueIndexReader $indexReader,
        private OrderProcessingState $processingState,
        private QueueRowRelease $rowRelease,
        private PluginLogger $logger,
    ) {
    }

    public function sweep(): void
    {
        $this->removeTooOld();
        $this->removeOrphaned();
    }

    private function removeTooOld(): void
    {
        $cutoff = time() - self::MAX_AGE_SECONDS;
        $orderIds = $this->indexReader->ordersOlderThan($cutoff, self::MAX_ROWS);

        foreach ($orderIds as $orderId) {
            $this->rowRelease->release($orderId);
            $this->logger->info(
                LogCode::QUEUE_ENTRY_TOO_OLD,
                ['maxAgeSeconds' => self::MAX_AGE_SECONDS],
                $orderId
            );
        }
    }

    private function removeOrphaned(): void
    {
        $orderIds = $this->indexReader->allOrderIds(self::MAX_ROWS);

        $removed = 0;

        foreach ($orderIds as $orderId) {
            $state = $this->processingState->stateOf($orderId);

            if ($state === ProcessState::FINISHED || $state === null) {
                $this->rowRelease->release($orderId);
                $this->logger->info(LogCode::QUEUE_ORPHAN_REMOVED, ['state' => $state], $orderId);
                $removed++;
            }
        }

        $this->logger->info(LogCode::QUEUE_ORPHAN_SWEEP_FINISHED, [
            'found' => count($orderIds),
            'removed' => $removed,
        ]);
    }
}
