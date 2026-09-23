<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Dtos\AddressDto;
use EnderecoPlentyOneClient\Dtos\CheckResult;
use EnderecoPlentyOneClient\Dtos\CheckSession;
use EnderecoPlentyOneClient\Enums\AttemptLimit;
use EnderecoPlentyOneClient\Enums\LogCode;
use EnderecoPlentyOneClient\Enums\OrderPropertyName;
use EnderecoPlentyOneClient\Enums\ProcessState;

/**
 * Checks the address of one order, then closes it out.
 *
 * A failed attempt raises the attempt counter and leaves the order enqueued
 * for the next run - see recordFailedAttempt() for the circuit breaker that
 * eventually gives up. A successful answer moves the order to "checked"
 * first, then CheckResultWriter decides what the finding means for it.
 */
final class OrderCheckService
{
    public function __construct(
        private OrderAddressReader $addresses,
        private EnderecoApi $api,
        private OrderPropertyWriter $properties,
        private QueueIndexWriter $indexWriter,
        private AttemptCounter $attempts,
        private CheckResultWriter $results,
        private QueueRowRelease $rowRelease,
        private PluginLogger $logger,
    ) {
    }

    public function process(int $orderId): void
    {
        $address = $this->addresses->deliveryAddress($orderId);

        if ($address === null) {
            // Had one at enqueue time (OrderCheckEligibility) - lost since, won't return.
            $this->rowRelease->release($orderId);

            return;
        }

        try {
            // One session per attempt.
            $session = CheckSession::create();
            $result = $this->api->checkAddress($address, $session);
        } catch (\Throwable $exception) {
            $this->recordFailedAttempt($orderId, $address, $exception);

            return;
        }

        $this->recordChecked($orderId, $address, $result);
    }

    /**
     * The circuit breaker: past AttemptLimit::MAX_ATTEMPTS, gives up via
     * CheckResultWriter::giveUp() instead of retrying.
     */
    private function recordFailedAttempt(
        int $orderId,
        AddressDto $address,
        \Throwable $exception
    ): void {
        try {
            $attempts = $this->attempts->next($orderId);
        } catch (\Throwable) {
            // Order deleted before the attempt could be counted.
            $this->logger->exception(LogCode::API_CALL_FAILED, $exception, [], $orderId);

            return;
        }

        $this->properties->write($orderId, [
            OrderPropertyName::ATTEMPTS_COUNT => $attempts,
        ]);

        if ((int) $attempts >= AttemptLimit::MAX_ATTEMPTS) {
            $this->logger->exception(
                LogCode::ORDER_GAVE_UP,
                $exception,
                ['attempts' => $attempts, 'maxAttempts' => AttemptLimit::MAX_ATTEMPTS],
                $orderId
            );

            $this->results->giveUp($orderId, $address, $attempts);

            return;
        }

        $this->logger->exception(
            LogCode::API_CALL_FAILED,
            $exception,
            ['attempts' => $attempts, 'maxAttempts' => AttemptLimit::MAX_ATTEMPTS],
            $orderId
        );
    }

    /**
     * The CHECKED write is best-effort, not a gate: hands off to
     * CheckResultWriter regardless, so a result already in hand is never
     * thrown away for a repeat, billed API call over a property write hiccup.
     */
    private function recordChecked(
        int $orderId,
        AddressDto $address,
        CheckResult $result
    ): void {
        $this->properties->write($orderId, [
            OrderPropertyName::PROCESS_STATE => ProcessState::CHECKED,
        ]);

        $this->indexWriter->moveTo($orderId, ProcessState::CHECKED);

        $this->logger->info(LogCode::ORDER_CHECKED, [
            'finding' => $result->finding(),
            'statusCodes' => $result->statusCodesAsText(),
        ], $orderId);

        $this->results->write($orderId, $address, $result);
    }
}
