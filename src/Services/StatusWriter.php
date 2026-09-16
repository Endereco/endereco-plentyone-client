<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Enums\LogCode;
use EnderecoPlentyOneClient\Enums\OrderPropertyName;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;

/**
 * Writes the order statuses this plugin manages: putting an order on its
 * start status while it is checked, and on its resolved target status once
 * the check is closed out.
 */
final class StatusWriter
{
    public function __construct(
        private OrderRepositoryContract $orders,
        private OrderPropertyReader $properties,
        private StatusResolver $statusResolver,
        private PluginLogger $logger,
    ) {
    }

    /**
     * Puts an order on its start status and reports the status it came
     * from, for later restoration. Already there: no write, but still
     * reported.
     */
    public function writeStart(int $orderId, float $startStatusId): ?string
    {
        try {
            $order = $this->orders->findById($orderId);
            $before = (string) $order->statusId;

            if ((float) $order->statusId === $startStatusId) {
                return $before;
            }

            $this->orders->update(
                $orderId,
                ['statusId' => $startStatusId]
            );
        } catch (\Throwable $exception) {
            $this->logger->exception(
                LogCode::ORDER_QUEUE_FAILED,
                $exception,
                [],
                $orderId
            );

            return null;
        }

        return $before;
    }

    /**
     * Undoes writeStart() when enqueueing fails after it.
     */
    public function revertStart(int $orderId, string $before): void
    {
        $this->write($orderId, (float) $before, LogCode::START_STATUS_REVERTED);
    }

    /**
     * Puts the order on its resolved target status: a merchant-configured
     * override, or the status it had before the plugin held it.
     *
     * Writes nothing if there is no remembered status (plugin never touched
     * this order), or if the order is no longer on its start status - someone
     * else moved it since, and this must not undo that, override or not.
     */
    public function writeTarget(int $orderId, ?float $targetStatusId = null): void
    {
        if ($targetStatusId !== null) {
            $this->write($orderId, $targetStatusId, LogCode::TARGET_STATUS_SET);

            return;
        }

        $before = $this->statusBefore($orderId);

        if ($before === null) {
            $this->logger->info(LogCode::STATUS_KEPT, [
                'reason' => 'plugin never changed the status',
            ], $orderId);

            return;
        }

        $this->write($orderId, (float) $before, LogCode::STATUS_RESTORED);
    }

    /**
     * Null on a missing order, same as "the plugin never changed the status" -
     * there is nothing left to restore either way.
     */
    private function statusBefore(int $orderId): ?string
    {
        try {
            return $this->properties->value(
                $orderId,
                OrderPropertyName::STATUS_BEFORE
            );
        } catch (\Throwable) {
            return null;
        }
    }

    private function write(int $orderId, float $statusId, string $successCode): void
    {
        try {
            if (! $this->stillOnStartStatus($orderId)) {
                return;
            }

            $this->orders->update($orderId, ['statusId' => $statusId]);
        } catch (\Throwable $exception) {
            $this->logger->exception(
                LogCode::STATUS_WRITE_FAILED,
                $exception,
                ['statusId' => $statusId],
                $orderId
            );

            return;
        }

        $this->logger->info(
            $successCode,
            ['statusId' => $statusId],
            $orderId
        );
    }

    private function stillOnStartStatus(int $orderId): bool
    {
        $order = $this->orders->findById($orderId);
        $startStatusId = $this->statusResolver->resolveStart($orderId);

        if ((float) $order->statusId === $startStatusId) {
            return true;
        }

        $this->logger->info(LogCode::STATUS_KEPT, [
            'reason' => 'someone moved the order on',
            'statusId' => $order->statusId,
        ], $orderId);

        return false;
    }
}
