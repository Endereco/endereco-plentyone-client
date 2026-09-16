<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Enums\LogCode;
use EnderecoPlentyOneClient\Enums\ProcessState;

/**
 * Hands out the next orders of a stage.
 *
 * Before handing an order out, its actual property state is compared
 * against the state recorded in the index; on a mismatch the order is not
 * added to the batch, and its index row is dropped instead.
 */
final class QueueBatch
{
    public function __construct(
        private QueueIndexReader $indexReader,
        private QueueRowRelease $rowRelease,
        private OrderProcessingState $processingState,
        private PluginLogger $logger,
    ) {
    }

    /**
     * Orders waiting for their address check.
     *
     * @return list<int>
     */
    public function pending(int $limit): array
    {
        return $this->inStage(ProcessState::ENQUEUED, $limit);
    }

    /** @return list<int> */
    private function inStage(string $state, int $limit): array
    {
        $orderIds = [];

        foreach ($this->indexReader->filteredOrderIds($state, $limit) as $orderId) {
            if ($this->processingState->stateOf($orderId) === $state) {
                $orderIds[] = $orderId;
            } else {
                $this->rowRelease->release($orderId);
                $this->logger->info(
                    LogCode::QUEUE_ENTRY_STALE,
                    ['expected' => $state],
                    $orderId
                );
            }
        }

        return $orderIds;
    }
}
