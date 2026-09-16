<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Dtos\AddressDto;
use EnderecoPlentyOneClient\Dtos\CheckResult;
use EnderecoPlentyOneClient\Enums\CheckFinding;
use EnderecoPlentyOneClient\Enums\LogCode;

/**
 * Decides whether a correction is written to the order, and writes it.
 *
 * Requires a correctable finding, a suggestion, and the merchant's "apply
 * automatically" setting - with the setting off, OrderPropertyPayload still
 * records the suggestion, just without touching the address.
 */
final class CorrectionApplier
{
    public function __construct(
        private OrderAddressWriter $addressWriter,
        private PluginSettings $settings,
        private PluginLogger $logger,
    ) {
    }

    /** True when the address on the order was actually changed. */
    public function apply(
        int $orderId,
        AddressDto $original,
        CheckResult $result
    ): bool {
        $suggestion = $result->suggestion();

        if (
            $result->finding() !== CheckFinding::CORRECTABLE
            || $suggestion === null
        ) {
            return false;
        }

        if (! $this->settings->appliesCorrectionAutomatically()) {
            $this->logger->info(
                LogCode::CORRECTION_KEPT_BACK,
                ['streetBefore' => $original->address1()],
                $orderId
            );

            return false;
        }

        if (! $this->addressWriter->apply($orderId, $suggestion)) {
            return false;
        }

        $this->logger->info(LogCode::CORRECTION_APPLIED, [
            'streetBefore' => $original->address1(),
            'streetAfter' => $suggestion->address1(),
            'placeBefore' => $original->postalCode() . ' ' . $original->town(),
            'placeAfter' => $suggestion->postalCode() . ' '
                . $suggestion->town(),
        ], $orderId);

        return true;
    }
}
