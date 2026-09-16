<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Enums\LogCode;

/**
 * Queues an order, triggered by a flow - shared logic behind both flow
 * entry points (EnqueueOrderProcedure, EnqueueOrderFlowAction).
 *
 * Unlike the OrderCreated listener, a merchant explicitly asking for a
 * check may re-run one this plugin already finished.
 */
final class EnqueueOrderFromFlow
{
    public function __construct(
        private OrderCheckEligibility $eligibility,
        private OrderQueueService $queue,
        private PluginSettings $settings,
        private PluginLogger $logger,
    ) {
    }

    public function handle(int $orderId): void
    {
        try {
            if (! $this->eligibility->isEligible($orderId)) {
                return;
            }

            if (! $this->settings->acceptsNewOrders()) {
                $this->logger->info(LogCode::ORDER_SKIPPED_NOT_ACCEPTING, [], $orderId);

                return;
            }

            $this->queue->enqueueFromFlow($orderId);
        } catch (\Throwable $exception) {
            $this->logger->exception(
                LogCode::ORDER_QUEUE_FAILED,
                $exception,
                ['trigger' => 'flow'],
                $orderId
            );
        }
    }
}
