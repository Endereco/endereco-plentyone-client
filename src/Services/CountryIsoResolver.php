<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Exceptions\AddressCheckFailed;
use Plenty\Modules\Order\Shipping\Countries\Contracts\CountryRepositoryContract;

/**
 * Turns a PlentyONE country id into the ISO code the service expects.
 */
final class CountryIsoResolver
{
    /** Tells CountryRepositoryContract which ISO code variant to return. */
    private const ISO_CODE_2 = 'isoCode2';

    /**
     * Cache for the request.
     *
     * @var array<int, string>
     */
    private array $isoCodes = [];

    public function __construct(
        private CountryRepositoryContract $countries,
    ) {
    }

    /** @throws AddressCheckFailed */
    public function iso2(int $countryId): string
    {
        if (isset($this->isoCodes[$countryId])) {
            return $this->isoCodes[$countryId];
        }

        try {
            $isoCode = $this->countries->findIsoCode(
                $countryId,
                self::ISO_CODE_2
            );
        } catch (\Throwable $exception) {
            throw new AddressCheckFailed(
                'Unknown country id ' . $countryId . '.',
                0,
                $exception
            );
        }

        $this->isoCodes[$countryId] = $isoCode;

        return $isoCode;
    }
}
