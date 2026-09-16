<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Dtos\AddressDto;
use EnderecoPlentyOneClient\Enums\LogCode;
use Plenty\Modules\Account\Address\Models\Address;
use Plenty\Modules\Account\Address\Models\AddressRelationType;
use Plenty\Modules\Order\Address\Contracts\OrderAddressRepositoryContract;

/**
 * Reads the delivery address of an order.
 */
final class OrderAddressReader
{
    public function __construct(
        private OrderAddressRepositoryContract $addresses,
        private PluginLogger $logger,
    ) {
    }

    public function hasDeliveryAddress(int $orderId): bool
    {
        return $this->findDeliveryAddress($orderId) !== null;
    }

    public function deliveryAddress(int $orderId): ?AddressDto
    {
        $address = $this->findDeliveryAddress($orderId);

        if ($address === null) {
            $this->logger->info(LogCode::ADDRESS_MISSING, [], $orderId);

            return null;
        }

        return $this->toDto($address);
    }

    private function findDeliveryAddress(int $orderId): ?Address
    {
        try {
            return $this->addresses->findAddressByType(
                $orderId,
                AddressRelationType::DELIVERY_ADDRESS
            );
        } catch (\Throwable) {
            return null;
        }
    }

    private function toDto(Address $address): AddressDto
    {
        return pluginApp(AddressDto::class, [
            (string) $address->address1,
            (string) $address->address2,
            (string) $address->address3,
            (string) $address->address4,
            (string) $address->postalCode,
            (string) $address->town,
            (int) $address->countryId,
            $address->stateId === null ? null : (int) $address->stateId,
        ]);
    }
}
