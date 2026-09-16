<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Enums\LogCode;
use EnderecoPlentyOneClient\Enums\OrderStatus;
use Plenty\Modules\Order\Status\Contracts\OrderStatusRepositoryContract;

/**
 * Resolves the merchant-configured order statuses this plugin uses,
 * both checked against the statuses that actually exist on this system.
 */
final class StatusResolver
{
    public function __construct(
        private OrderStatusRepositoryContract $statuses,
        private PluginSettings $settings,
        private PluginLogger $logger,
    ) {
    }

    /**
     * The status configured for an order to start its check on.
     *
     * Always returns something usable: falls back to OrderStatus::HOLD both
     * when nothing is configured and when a configured status does not exist.
     */
    public function resolveStart(int $orderId): float
    {
        $configured = $this->settings->startStatus();

        if ($this->exists($configured)) {
            return $configured;
        }

        $this->logger->error(
            LogCode::START_STATUS_MISSING,
            ['statusId' => $configured],
            $orderId
        );

        return (float) OrderStatus::HOLD;
    }

    /**
     * The status configured for one TargetStatusCase.
     *
     * Null means: no override, restore the status the order had before it
     * was queued - when nothing is configured, when "0" is configured
     * (explicitly no override), or when a configured status does not exist
     * in the system.
     */
    public function resolveTarget(int $orderId, string $case): ?float
    {
        $configured = $this->settings->targetStatus($case);

        if ($configured === null) {
            return null;
        }

        if ($this->exists($configured)) {
            return $configured;
        }

        $this->logger->error(LogCode::TARGET_STATUS_MISSING, [
            'case' => $case,
            'statusId' => $configured,
        ], $orderId);

        return null;
    }

    private function exists(float $statusId): bool
    {
        try {
            $this->statuses->get($statusId);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
