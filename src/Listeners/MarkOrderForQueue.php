<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Listeners;

use EnderecoPlentyOneClient\Enums\LogCode;
use EnderecoPlentyOneClient\Services\OrderCheckEligibility;
use EnderecoPlentyOneClient\Services\OrderQueueService;
use EnderecoPlentyOneClient\Services\PluginLogger;
use EnderecoPlentyOneClient\Services\PluginSettings;
use Plenty\Modules\Order\Events\OrderCreated;

/**
 * In automatic mode, every new sales order is queued on the OrderCreated
 * event - a flow action can also enqueue one, independently of this.
 */
final class MarkOrderForQueue
{
    public function __construct(
        private OrderCheckEligibility $eligibility,
        private OrderQueueService $queue,
        private PluginSettings $settings,
        private PluginLogger $logger,
    ) {
    }

    /** handle() is the method PlentyONE calls for a class listener. */
    public function handle(OrderCreated $event): void
    {
        $orderId = $event->getOrder()->id;

        try {
            if (! $this->eligibility->isEligible($orderId)) {
                return;
            }

            if (! $this->settings->enqueuesAutomatically()) {
                $this->logger->info(LogCode::ORDER_SKIPPED_MANUAL_MODE, [], $orderId);

                return;
            }

            if (! $this->settings->acceptsNewOrders()) {
                $this->logger->info(LogCode::ORDER_SKIPPED_NOT_ACCEPTING, [], $orderId);

                return;
            }

            $this->queue->enqueueFromListener($orderId);
        } catch (\Throwable $exception) {
            $this->logger->exception(
                LogCode::ORDER_QUEUE_FAILED,
                $exception,
                ['trigger' => 'orderCreated'],
                $orderId
            );
        }
    }
}
