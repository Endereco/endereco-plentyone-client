<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Dtos\AddressDto;
use EnderecoPlentyOneClient\Enums\OrderPropertyName;
use EnderecoPlentyOneClient\Enums\PropertyValue;

/**
 * Spreads an address over the plugin's order properties, one field per
 * property - the address before the check and the one the service answered
 * with, each as its own set so plentyFlow can filter on individual fields.
 *
 * Every field goes through orNone(): an empty string does not overwrite a
 * value a previous check left behind, so a field going from "has a value"
 * to empty needs a real placeholder instead.
 */
final class AddressFields
{
    public function __construct(
        private SubdivisionResolver $subdivisions,
    ) {
    }

    /** @return array<string, string> */
    public function original(AddressDto $address): array
    {
        return [
            OrderPropertyName::ADDRESS1_ORIGINAL => $this->orNone($address->address1()),
            OrderPropertyName::ADDRESS2_ORIGINAL => $this->orNone($address->address2()),
            OrderPropertyName::ADDRESS3_ORIGINAL => $this->orNone($address->address3()),
            OrderPropertyName::ADDRESS4_ORIGINAL => $this->orNone($address->address4()),
            OrderPropertyName::POSTAL_CODE_ORIGINAL => $this->orNone($address->postalCode()),
            OrderPropertyName::TOWN_ORIGINAL => $this->orNone($address->town()),
            OrderPropertyName::SUBDIVISION_ORIGINAL => $this->orNone($this->subdivision($address)),
        ];
    }

    /** @return array<string, string> */
    public function suggestion(AddressDto $address): array
    {
        return [
            OrderPropertyName::ADDRESS1_SUGGESTION => $this->orNone($address->address1()),
            OrderPropertyName::ADDRESS2_SUGGESTION => $this->orNone($address->address2()),
            OrderPropertyName::ADDRESS3_SUGGESTION => $this->orNone($address->address3()),
            OrderPropertyName::ADDRESS4_SUGGESTION => $this->orNone($address->address4()),
            OrderPropertyName::POSTAL_CODE_SUGGESTION => $this->orNone($address->postalCode()),
            OrderPropertyName::TOWN_SUGGESTION => $this->orNone($address->town()),
            OrderPropertyName::SUBDIVISION_SUGGESTION => $this->orNone($this->subdivision($address)),
        ];
    }

    /**
     * The "suggestion" fields, all set to the same placeholder.
     *
     * @return array<string, string>
     */
    public function suggestionPlaceholder(string $value): array
    {
        return array_fill_keys([
            OrderPropertyName::ADDRESS1_SUGGESTION,
            OrderPropertyName::ADDRESS2_SUGGESTION,
            OrderPropertyName::ADDRESS3_SUGGESTION,
            OrderPropertyName::ADDRESS4_SUGGESTION,
            OrderPropertyName::POSTAL_CODE_SUGGESTION,
            OrderPropertyName::TOWN_SUGGESTION,
            OrderPropertyName::SUBDIVISION_SUGGESTION,
        ], $value);
    }

    /** An unresolvable state becomes an empty value, not an exception. */
    private function subdivision(AddressDto $address): string
    {
        try {
            $code = $this->subdivisions->plentyToIso(
                $address->countryId(),
                $address->stateId()
            );
        } catch (\Throwable) {
            return '';
        }

        return $code ?? '';
    }

    private function orNone(string $value): string
    {
        return $value === '' ? PropertyValue::NONE : $value;
    }
}
