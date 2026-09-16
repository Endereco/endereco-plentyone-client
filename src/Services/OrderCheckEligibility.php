<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Enums\LogCode;
use EnderecoPlentyOneClient\Enums\OrderType;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;

/**
 * Whether an order is worth an address check at all: a sales order
 * with a delivery address.
 */
final class OrderCheckEligibility
{
    public function __construct(
        private OrderRepositoryContract $orders,
        private OrderAddressReader $addressReader,
        private PluginLogger $logger,
    ) {
    }

    /** @throws \Throwable propagated from looking up the order. */
    public function isEligible(int $orderId): bool
    {
        if ($this->orders->findById($orderId)->typeId !== OrderType::SALES_ORDER) {
            $this->logger->info(LogCode::ORDER_SKIPPED_NOT_SALES_ORDER, [], $orderId);

            return false;
        }

        if (! $this->addressReader->hasDeliveryAddress($orderId)) {
            $this->logger->info(LogCode::ORDER_SKIPPED_NO_ADDRESS, [], $orderId);

            return false;
        }

        return true;
    }
}
