<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Dtos\CheckSession;
use EnderecoPlentyOneClient\Enums\EnderecoRpc;
use EnderecoPlentyOneClient\Exceptions\AddressCheckFailed;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;

/**
 * Sends one request to the service and hands back its answer.
 *
 * Transport only - turns three failure kinds into an AddressCheckFailed:
 * broken connection, non-200 HTTP, or a JSON-RPC error (a wrong API key
 * included; a missing one is caught before the call is even made).
 *
 * The HTTP call itself happens in resources/lib/Rpc.php - see
 * EnderecoRpc::LIB_CALL for why it cannot happen here.
 */
final class EnderecoRpcClient
{
    public function __construct(
        private LibraryCallContract $library,
        private PluginSettings $settings,
    ) {
    }

    /**
     * @param array<string, string> $params
     *
     * @return array<string, string|list<string>|list<array<string, string>>>
     *
     * @throws AddressCheckFailed
     */
    public function call(
        string $method,
        array $params,
        CheckSession $session
    ): array {
        $apiKey = $this->settings->apiKey();

        if ($apiKey === '') {
            throw new AddressCheckFailed(
                'No API key is configured for the address check.'
            );
        }

        $answer = $this->library->call(EnderecoRpc::LIB_CALL, [
            EnderecoRpc::PARAM_ENDPOINT => $this->settings->apiEndpoint(),
            EnderecoRpc::PARAM_API_KEY => $apiKey,
            EnderecoRpc::PARAM_AGENT => EnderecoRpc::AGENT,
            EnderecoRpc::PARAM_SESSION_ID => $session->id(),
            EnderecoRpc::PARAM_METHOD => $method,
            EnderecoRpc::PARAM_PARAMS => $params,
        ]);

        $this->checkForError($answer);

        return $answer;
    }

    /**
     * @param array<string, string|list<string>|list<array<string, string>>> $answer
     *
     * @throws AddressCheckFailed
     */
    private function checkForError(array $answer): void
    {
        $transportError = $this->text($answer, EnderecoRpc::KEY_TRANSPORT_ERROR);

        if ($transportError !== '') {
            throw new AddressCheckFailed($transportError);
        }

        $httpCode = $this->text($answer, EnderecoRpc::KEY_HTTP_CODE);

        if ($httpCode !== EnderecoRpc::HTTP_OK) {
            throw new AddressCheckFailed(
                'The service answered with HTTP ' . $httpCode . '.'
            );
        }

        $rpcError = $this->text($answer, EnderecoRpc::KEY_RPC_ERROR);

        if ($rpcError !== '') {
            throw new AddressCheckFailed($rpcError);
        }
    }

    /**
     * @param array<string, string|list<string>|list<array<string, string>>> $answer
     */
    private function text(array $answer, string $key): string
    {
        $value = $answer[$key] ?? '';

        return is_string($value) ? $value : '';
    }
}
