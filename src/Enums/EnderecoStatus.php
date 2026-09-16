<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Enums;

/**
 * Status codes the endereco service's addressCheck can return - see
 * EnderecoResultMapper for how they turn into a finding.
 */
final class EnderecoStatus
{
    public const ADDRESS_CORRECT = 'address_correct';

    public const ADDRESS_NEEDS_CORRECTION = 'address_needs_correction';

    /** Typo class: the answer means the same address, spelled properly. */
    public const ADDRESS_MINOR_CORRECTION = 'address_minor_correction';

    /** Moves the address somewhere else - not applied automatically. */
    public const ADDRESS_MAJOR_CORRECTION = 'address_major_correction';

    /** More than one candidate - a machine cannot pick for the customer. */
    public const ADDRESS_MULTIPLE_VARIANTS = 'address_multiple_variants';

    /** No candidate at all. */
    public const ADDRESS_NOT_FOUND = 'address_not_found';

    public const ADDRESS_IS_POSTOFFICE = 'address_is_postoffice';

    public const ADDRESS_IS_PACKSTATION = 'address_is_packstation';

    public const BUILDING_NUMBER_IS_MISSING = 'building_number_is_missing';

    public const BUILDING_NUMBER_NOT_FOUND = 'building_number_not_found';

    /**
     * The Packstation/Postfiliale customer number (PlentyONE's address2 for
     * such an address) is missing or wrong - only meaningful together with
     * IS_PACKSTATION or IS_POSTOFFICE.
     */
    public const ADDITIONAL_INFO_NOT_FOUND = 'additional_info_not_found';
}
