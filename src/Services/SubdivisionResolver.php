<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Exceptions\AddressCheckFailed;
use Plenty\Modules\Order\Shipping\Countries\Contracts\CountryRepositoryContract;

/**
 * Translates between PlentyONE's state id and the service's ISO 3166-2
 * subdivision code ("BY" vs "DE-BY"), in both directions.
 */
final class SubdivisionResolver
{
    private const SEPARATOR = '-';

    /** Length of "XX-" in front of an ISO 3166-2 code. */
    private const PREFIX_LENGTH = 3;

    public function __construct(
        private CountryRepositoryContract $countries,
        private CountryIsoResolver $countryIsoResolver,
    ) {
    }

    /**
     * The subdivisionCode to send, or null when the field should not be
     * sent at all.
     *
     * Sent whenever the country has any subdivisions configured in
     * PlentyONE, regardless of whether this address picked one. Omitted
     * only for a country with none configured at all (e.g. Austria).
     *
     * @throws AddressCheckFailed propagated from resolving the country's
     *   ISO code - practically unreachable here since build() already
     *   resolved and cached it before this runs.
     */
    public function plentyToIso(int $countryId, ?int $stateId): ?string
    {
        $states = $this->statesOf($countryId);

        if ($states === []) {
            return null;
        }

        if ($stateId === null) {
            return '';
        }

        foreach ($states as $state) {
            if ($state->id === $stateId) {
                return $this->countryIsoResolver->iso2($countryId)
                    . self::SEPARATOR
                    . $state->isoCode;
            }
        }

        return '';
    }

    /** The PlentyONE state id an ISO 3166-2 code matches, or null when it doesn't. */
    public function isoToPlenty(int $countryId, string $code): ?int
    {
        $shortCode = substr($code, self::PREFIX_LENGTH);

        if ($shortCode === '') {
            return null;
        }

        try {
            $state = $this->countries->getCountryStateByIso(
                $countryId,
                $shortCode
            );
        } catch (\Throwable) {
            return null;
        }

        return $state->id;
    }

    /**
     * Every state PlentyONE has configured for this country - empty when it
     * has none, which is the signal to leave subdivisionCode out entirely.
     *
     * @return list<object>
     */
    private function statesOf(int $countryId): array
    {
        try {
            $country = $this->countries->getCountryById($countryId);
        } catch (\Throwable) {
            return [];
        }

        $states = [];

        foreach ($country->states as $state) {
            $states[] = $state;
        }

        return $states;
    }
}
