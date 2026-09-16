<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Dtos\AddressDto;
use EnderecoPlentyOneClient\Dtos\CheckResult;
use EnderecoPlentyOneClient\Enums\CheckFinding;
use EnderecoPlentyOneClient\Enums\CheckOutcome;
use EnderecoPlentyOneClient\Enums\OrderPropertyName;
use EnderecoPlentyOneClient\Enums\ProcessState;
use EnderecoPlentyOneClient\Enums\PropertyValue;

/**
 * Builds the properties an answered check leaves on the order.
 *
 * The original address is always written - the merchant's undo for whatever
 * correction was applied. Suggestion fields are always written too, even as
 * a placeholder, so a re-check never leaves a previous result's fields
 * standing.
 */
final class OrderPropertyPayload
{
    public function __construct(
        private AddressFields $fields,
    ) {
    }

    /** @return array<string, string> */
    public function checked(
        AddressDto $original,
        CheckResult $result,
        string $outcome,
        bool $applied
    ): array {
        $values = [
            OrderPropertyName::PROCESS_STATE => ProcessState::FINISHED,
            OrderPropertyName::CHECK_OUTCOME => $outcome,
            OrderPropertyName::CORRECTION_APPLIED => $applied
                ? PropertyValue::TRUE
                : PropertyValue::FALSE,
        ];

        $values = array_merge($values, $this->fields->original($original));
        $values = array_merge($values, $this->suggestion($result));

        return $values;
    }

    /**
     * The properties of an order the circuit breaker gave up on - original
     * address still written, suggestion fields set to "none".
     *
     * @return array<string, string>
     */
    public function gaveUp(AddressDto $original, string $attempts): array
    {
        $values = [
            OrderPropertyName::PROCESS_STATE => ProcessState::FINISHED,
            OrderPropertyName::ATTEMPTS_COUNT => $attempts,
            OrderPropertyName::CHECK_OUTCOME => CheckOutcome::FAILED,
            OrderPropertyName::CORRECTION_APPLIED => PropertyValue::FALSE,
        ];

        $values = array_merge($values, $this->fields->original($original));
        $values = array_merge($values, $this->fields->suggestionPlaceholder(PropertyValue::NONE));

        return $values;
    }

    /**
     * A single suggestion is written field by field. Several to choose from,
     * or none at all, get the same placeholder in every field instead.
     *
     * @return array<string, string>
     */
    private function suggestion(CheckResult $result): array
    {
        $suggestion = $result->suggestion();

        if ($suggestion !== null) {
            return $this->fields->suggestion($suggestion);
        }

        $placeholder = $result->finding() === CheckFinding::MULTIPLE_VARIANTS
            ? PropertyValue::MULTIPLE
            : PropertyValue::NONE;

        return $this->fields->suggestionPlaceholder($placeholder);
    }
}
