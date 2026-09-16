<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Dtos\AddressDto;
use EnderecoPlentyOneClient\Dtos\CheckResult;
use EnderecoPlentyOneClient\Enums\CheckFinding;
use EnderecoPlentyOneClient\Enums\EnderecoStatus;
use EnderecoPlentyOneClient\Exceptions\AddressCheckFailed;

/**
 * Turns the service's answer into one of five findings.
 *
 * - "address_correct": fine as it stands - unless the service's street
 *   differs from address1 (e.g. it held street and house number combined).
 *   That case is deliberately upgraded to correctable instead, so it is
 *   written and tagged like any other minor correction (e.g.
 *   ADDRESS_CORRECTED), not treated as a special case.
 * - "address_minor_correction" with exactly one suggestion: correctable.
 * - Otherwise: "address_multiple_variants", "address_not_found",
 *   "address_major_correction" (a suggestion exists but isn't minor, so it
 *   is never applied automatically).
 *
 * A status combination matching none of the above is treated the same as a
 * failed API call, not guessed at - see AddressCheckFailed.
 */
final class EnderecoResultMapper
{
    public function __construct(
        private EnderecoStatusCodeReader $codes,
        private EnderecoPredictionReader $predictions,
        private EnderecoPredictionMapper $predictionMapper,
    ) {
    }

    /**
     * @param array<string, string|list<string>|list<array<string, string>>> $answer
     *
     * @throws AddressCheckFailed
     */
    public function map(array $answer, AddressDto $original): CheckResult
    {
        $codes = $this->codes->read($answer);
        $singlePrediction = $this->predictions->single($answer);
        $allPredictions = $this->predictions->all($answer);

        if (in_array(EnderecoStatus::ADDRESS_CORRECT, $codes, true)) {
            $suggestion = $this->predictionMapper->orOriginal($original, $singlePrediction);

            $streetChanged = $singlePrediction !== null
                && $this->predictionMapper->streetFieldChanged($original, $singlePrediction);

            if ($streetChanged) {
                return $this->result(CheckFinding::CORRECTABLE, $suggestion, $codes, $allPredictions);
            }

            return $this->result(CheckFinding::CORRECT, $suggestion, $codes, $allPredictions);
        }

        if ($singlePrediction !== null && in_array(EnderecoStatus::ADDRESS_MINOR_CORRECTION, $codes, true)) {
            $suggestion = $this->predictionMapper->toAddress($original, $singlePrediction);

            return $this->result(CheckFinding::CORRECTABLE, $suggestion, $codes, $allPredictions);
        }

        if (in_array(EnderecoStatus::ADDRESS_MULTIPLE_VARIANTS, $codes, true)) {
            return $this->result(CheckFinding::MULTIPLE_VARIANTS, null, $codes, $allPredictions);
        }

        if (in_array(EnderecoStatus::ADDRESS_NOT_FOUND, $codes, true)) {
            return $this->result(CheckFinding::NOT_FOUND, null, $codes, $allPredictions);
        }

        if (in_array(EnderecoStatus::ADDRESS_MAJOR_CORRECTION, $codes, true)) {
            $suggestion = $singlePrediction !== null
                ? $this->predictionMapper->toAddress($original, $singlePrediction)
                : null;

            return $this->result(CheckFinding::MAJOR_CORRECTION, $suggestion, $codes, $allPredictions);
        }

        throw new AddressCheckFailed('Unrecognized status codes: ' . implode(', ', $codes));
    }

    /**
     * @param list<string> $codes
     * @param list<array<string, string>> $allPredictions
     */
    private function result(
        string $finding,
        ?AddressDto $suggestion,
        array $codes,
        array $allPredictions
    ): CheckResult {
        return pluginApp(CheckResult::class, [$finding, $suggestion, $codes, $allPredictions]);
    }
}
