<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Dtos\AddressDto;
use EnderecoPlentyOneClient\Dtos\CheckResult;
use EnderecoPlentyOneClient\Dtos\CheckSession;
use EnderecoPlentyOneClient\Enums\EnderecoRpc;
use EnderecoPlentyOneClient\Exceptions\AddressCheckFailed;

/**
 * The plugin's door to the endereco service - everything that wants an
 * address checked talks to this class, nothing else. Building the request,
 * sending it, and reading the answer are three separate concerns behind it.
 */
final class EnderecoApi
{
    public function __construct(
        private EnderecoRequestBuilder $requests,
        private EnderecoRpcClient $client,
        private EnderecoResultMapper $resultMapper,
    ) {
    }

    /** @throws AddressCheckFailed */
    public function checkAddress(
        AddressDto $address,
        CheckSession $session
    ): CheckResult {
        $answer = $this->client->call(
            EnderecoRpc::METHOD_ADDRESS_CHECK,
            $this->requests->build($address),
            $session
        );

        return $this->resultMapper->map($answer, $address);
    }
}
