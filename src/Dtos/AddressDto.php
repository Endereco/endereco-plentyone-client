<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Dtos;

use EnderecoPlentyOneClient\Enums\AddressField;

/**
 * The address part of a PlentyONE order address.
 *
 * address1 is the street, address2 the house number - or, for a Packstation
 * or Postfiliale address, address1 that word and address2 the associated
 * number.
 *
 * Immutable; a correction produces a copy through withCorrection().
 */
final class AddressDto
{
    public function __construct(
        private string $address1,
        private string $address2,
        private string $address3,
        private string $address4,
        private string $postalCode,
        private string $town,
        private int $countryId,
        private ?int $stateId,
    ) {
    }

    /** Street, or street+house number combined if the checkout never split it. */
    public function address1(): string
    {
        return $this->address1;
    }

    /** House number - empty when address1 already carries it combined. */
    public function address2(): string
    {
        return $this->address2;
    }

    /** Additional line - what the service calls additionalInfo. */
    public function address3(): string
    {
        return $this->address3;
    }

    /** Free-form, undefined by PlentyONE; folded into additionalInfo too. */
    public function address4(): string
    {
        return $this->address4;
    }

    public function postalCode(): string
    {
        return $this->postalCode;
    }

    public function town(): string
    {
        return $this->town;
    }

    public function countryId(): int
    {
        return $this->countryId;
    }

    public function stateId(): ?int
    {
        return $this->stateId;
    }

    /** A copy with the fields an address check can correct. */
    public function withCorrection(
        string $address1,
        string $address2,
        string $address3,
        string $postalCode,
        string $town,
        ?int $stateId
    ): self {
        return pluginApp(self::class, [
            $address1,
            $address2,
            $address3,
            $this->address4,
            $postalCode,
            $town,
            $this->countryId,
            $stateId,
        ]);
    }

    /**
     * The field set for updateOrderAddress() - strictly only fields a
     * correction can actually contain. stateId is only included when set,
     * consistent with SubdivisionResolver treating it as optional throughout
     * the check.
     *
     * @return array<string, int|string|null>
     */
    public function toArray(): array
    {
        $data = [
            AddressField::ADDRESS1 => $this->address1,
            AddressField::ADDRESS2 => $this->address2,
            AddressField::ADDRESS3 => $this->address3,
            AddressField::POSTAL_CODE => $this->postalCode,
            AddressField::TOWN => $this->town,
        ];

        if ($this->stateId !== null) {
            $data[AddressField::STATE_ID] = $this->stateId;
        }

        return $data;
    }
}
