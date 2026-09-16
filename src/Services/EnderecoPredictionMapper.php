<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Dtos\AddressDto;
use EnderecoPlentyOneClient\Enums\EnderecoRpc;

/**
 * Puts a suggestion of the service back into a PlentyONE address.
 *
 * Six fields can differ - see AddressDto::withCorrection(). Country stays
 * as the customer entered it. additionalInfo is split back apart from
 * EnderecoRpc::ADDITIONAL_INFO_SEPARATOR - see EnderecoRequestBuilder.
 */
final class EnderecoPredictionMapper
{
    public function __construct(
        private SubdivisionResolver $subdivisions,
    ) {
    }

    /** @param array<string, string> $prediction */
    public function toAddress(
        AddressDto $original,
        array $prediction
    ): AddressDto {
        [$address3, $address4] = $this->additionalInfo($prediction);

        return $original->withCorrection(
            $this->field($prediction, EnderecoRpc::FIELD_STREET),
            $this->field($prediction, EnderecoRpc::FIELD_HOUSE_NUMBER),
            $address3,
            $address4,
            $this->field($prediction, EnderecoRpc::FIELD_POST_CODE),
            $this->field($prediction, EnderecoRpc::FIELD_CITY_NAME),
            $this->state($original, $prediction),
        );
    }

    /**
     * The address an already correct answer confirms - content-identical to
     * the original. Falls back to the original only if the suggestion the
     * API should always send alongside address_correct is missing.
     *
     * @param array<string, string>|null $prediction
     */
    public function orOriginal(
        AddressDto $original,
        ?array $prediction
    ): AddressDto {
        if ($prediction === null) {
            return $original;
        }

        return $this->toAddress($original, $prediction);
    }

    /**
     * Whether the prediction's street differs from address1 as it already
     * is - the service does not flag this as something to correct on its
     * own, so an otherwise "address is correct" answer can still need a
     * write (e.g. address1 held street and house number combined).
     *
     * @param array<string, string> $prediction
     */
    public function streetFieldChanged(AddressDto $original, array $prediction): bool
    {
        return $this->field($prediction, EnderecoRpc::FIELD_STREET) !== $original->address1();
    }

    /**
     * @param array<string, string> $prediction
     *
     * @return array{0: string, 1: string} address3, address4
     */
    private function additionalInfo(array $prediction): array
    {
        $value = $this->field($prediction, EnderecoRpc::FIELD_ADDITIONAL_INFO);
        $parts = explode(EnderecoRpc::ADDITIONAL_INFO_SEPARATOR, $value, 2);

        return [$parts[0], $parts[1] ?? ''];
    }

    /**
     * The state the answer names, or the one the address already had.
     *
     * @param array<string, string> $prediction
     */
    private function state(
        AddressDto $original,
        array $prediction
    ): ?int {
        $code = $this->field($prediction, EnderecoRpc::FIELD_SUBDIVISION_CODE);

        if ($code === '') {
            return $original->stateId();
        }

        $stateId = $this->subdivisions->isoToPlenty(
            $original->countryId(),
            $code
        );

        return $stateId ?? $original->stateId();
    }

    /** @param array<string, string> $prediction */
    private function field(array $prediction, string $key): string
    {
        return $prediction[$key] ?? '';
    }
}
