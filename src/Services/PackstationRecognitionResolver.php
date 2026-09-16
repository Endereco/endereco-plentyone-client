<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Dtos\CheckResult;
use EnderecoPlentyOneClient\Enums\EnderecoStatus;

/**
 * Whether endereco's answer recognised the address as a Packstation or
 * Postfiliale - for OrderCommentWriter to mention, nothing else.
 *
 * This plugin does NOT act on the recognition beyond that: PlentyONE
 * already sets the order address's own isPackstation/isPostfiliale flags
 * itself as soon as a merchant or customer enters "Packstation"/
 * "Postfiliale" as the street, and neither PlentyONE nor endereco
 * recognise an abbreviation ("Packst.") as one - so a plugin-side flag
 * write would only ever repeat what PlentyONE had already worked out on
 * its own, never add a case it had missed. Tried and removed after live
 * testing confirmed exactly that. Worth revisiting only if that changes -
 * e.g. if endereco's recognition ever becomes more lenient than PlentyONE's
 * own street-text match.
 */
final class PackstationRecognitionResolver
{
    /** @return array{0: bool, 1: bool}|null */
    public function resolve(CheckResult $result): ?array
    {
        $codes = $result->statusCodes();

        if (in_array(EnderecoStatus::ADDRESS_IS_PACKSTATION, $codes, true)) {
            return [true, false];
        }

        if (in_array(EnderecoStatus::ADDRESS_IS_POSTOFFICE, $codes, true)) {
            return [false, true];
        }

        return null;
    }
}
