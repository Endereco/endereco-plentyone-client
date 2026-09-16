<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Dtos\AddressDto;
use EnderecoPlentyOneClient\Enums\EnderecoRpc;
use EnderecoPlentyOneClient\Enums\Language;
use EnderecoPlentyOneClient\Exceptions\AddressCheckFailed;

/**
 * Translates a PlentyONE address into the service's vocabulary.
 */
final class EnderecoRequestBuilder
{
    public function __construct(
        private CountryIsoResolver $countryIsoResolver,
        private SubdivisionResolver $subdivisions,
    ) {
    }

    /**
     * @return array<string, string>
     *
     * @throws AddressCheckFailed
     */
    public function build(AddressDto $address): array
    {
        $country = $this->countryIsoResolver->iso2($address->countryId());

        $params = [
            EnderecoRpc::FIELD_COUNTRY => $country,
            EnderecoRpc::FIELD_LANGUAGE => Language::GERMAN,
            EnderecoRpc::FIELD_POST_CODE => $address->postalCode(),
            EnderecoRpc::FIELD_CITY_NAME => $address->town(),
        ];

        return array_merge(
            $params,
            $this->addStreet($address),
            $this->additionalInfo($address),
            $this->maybeSubdivision($address)
        );
    }

    /**
     * Always sent split - the service accepts an empty houseNumber and a
     * combined address1 just as well as a real split, splitting it apart itself.
     *
     * @return array<string, string>
     */
    private function addStreet(AddressDto $address): array
    {
        return [
            EnderecoRpc::FIELD_STREET => $address->address1(),
            EnderecoRpc::FIELD_HOUSE_NUMBER => $address->address2(),
        ];
    }

    /**
     * address3/address4 (PlentyONE's additional-info fields), folded into
     * one. Always sent, empty or not.
     *
     * Joined with EnderecoRpc::ADDITIONAL_INFO_SEPARATOR, not a plain space,
     * so EnderecoPredictionMapper can split address3/address4 back apart when
     * writing a correction.
     *
     * @return array<string, string>
     */
    private function additionalInfo(AddressDto $address): array
    {
        $address3 = $this->replaceSeparator($address->address3());
        $address4 = $this->replaceSeparator($address->address4());

        $value = $address3 === '' || $address4 === ''
            ? $address3 . $address4
            : $address3 . EnderecoRpc::ADDITIONAL_INFO_SEPARATOR . $address4;

        return [EnderecoRpc::FIELD_ADDITIONAL_INFO => $value];
    }

    private function replaceSeparator(string $value): string
    {
        return str_replace(EnderecoRpc::ADDITIONAL_INFO_SEPARATOR, ' ', $value);
    }

    /**
     * subdivisionCode is sent only when the country has any subdivisions
     * configured in PlentyONE, and then even as an empty string when this
     * address has none selected.
     *
     * @return array<string, string>
     *
     * @throws AddressCheckFailed
     */
    private function maybeSubdivision(AddressDto $address): array
    {
        $subdivision = $this->subdivisions->plentyToIso(
            $address->countryId(),
            $address->stateId()
        );

        return $subdivision !== null
            ? [EnderecoRpc::FIELD_SUBDIVISION_CODE => $subdivision]
            : [];
    }
}
