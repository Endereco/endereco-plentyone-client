<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Dtos\AddressDto;
use EnderecoPlentyOneClient\Enums\LogCode;
use Plenty\Modules\Account\Address\Models\AddressRelationType;
use Plenty\Modules\Order\Address\Contracts\OrderAddressRepositoryContract;

/**
 * Writes a corrected delivery address back to the order.
 *
 * A partial field set is fine - confirmed live, see AddressDto::toArray().
 */
final class OrderAddressWriter
{
    public function __construct(
        private OrderAddressRepositoryContract $addresses,
        private PluginLogger $logger,
    ) {
    }

    public function apply(int $orderId, AddressDto $address): bool
    {
        $type = AddressRelationType::DELIVERY_ADDRESS;

        try {
            $current = $this->addresses->findAddressByType($orderId, $type);

            $this->addresses->updateOrderAddress(
                $address->toArray(),
                (int) $current->id,
                $orderId,
                $type
            );
        } catch (\Throwable $exception) {
            $this->logger->exception(
                LogCode::CORRECTION_FAILED,
                $exception,
                [],
                $orderId
            );

            return false;
        }

        return true;
    }
}
