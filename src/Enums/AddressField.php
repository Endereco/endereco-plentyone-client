<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Enums;

/**
 * Field names of a PlentyONE address.
 *
 * ADDRESS1 is the street, ADDRESS2 the house number. For a Packstation or
 * Postfiliale address, ADDRESS1 holds that word instead and ADDRESS2 the
 * associated number.
 */
final class AddressField
{
    public const ADDRESS1 = 'address1';

    public const ADDRESS2 = 'address2';

    public const ADDRESS3 = 'address3';

    public const ADDRESS4 = 'address4';

    public const POSTAL_CODE = 'postalCode';

    public const TOWN = 'town';

    public const COUNTRY_ID = 'countryId';

    public const STATE_ID = 'stateId';
}
