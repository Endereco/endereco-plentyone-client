<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Enums;

/**
 * The wire protocol of the Endereco service.
 *
 * Everything the plugin knows about the shape of the request and the answer is
 * collected here, split into three blocks: what goes into the library call,
 * what goes into the JSON-RPC params, and what comes back.
 *
 * The service is JSON-RPC 2.0. The call itself does not happen in the plugin
 * container - see the LIB_CALL comment.
 */
final class EnderecoRpc
{
    /**
     * The script under resources/lib that performs the HTTP call - a plugin
     * cannot open an outgoing connection itself, so LibraryCallContract runs
     * it in the SDK sandbox instead. Format: "<plugin>::<file without .php>".
     */
    public const LIB_CALL = 'EnderecoPlentyOneClient::Rpc';

    public const METHOD_ADDRESS_CHECK = 'addressCheck';

    public const DEFAULT_ENDPOINT = 'https://endereco-service.de/rpc/v1';

    /** Identifies the caller in the service's logs. */
    public const AGENT = 'EnderecoPlentyOneClient/0.1.0';

    // --- parameters of the library call -----------------------------------

    public const PARAM_ENDPOINT = 'endpoint';

    public const PARAM_API_KEY = 'apiKey';

    public const PARAM_AGENT = 'agent';

    public const PARAM_PARAMS = 'params';

    /** Which RPC method the generic library script should call. */
    public const PARAM_METHOD = 'method';

    public const PARAM_SESSION_ID = 'sessionId';

    // --- parameters of the RPC method -------------------------------------

    /** ISO-3166-1 alpha-2, lower case. */
    public const FIELD_COUNTRY = 'country';

    /** Only affects the language of the service's own texts. */
    public const FIELD_LANGUAGE = 'language';

    public const FIELD_POST_CODE = 'postCode';

    public const FIELD_CITY_NAME = 'cityName';

    public const FIELD_STREET = 'street';

    public const FIELD_HOUSE_NUMBER = 'houseNumber';

    public const FIELD_ADDITIONAL_INFO = 'additionalInfo';

    /**
     * The plugin's own separator for joining two values into one
     * additionalInfo field and splitting the answer back apart. Not part
     * of Endereco's protocol.
     */
    public const ADDITIONAL_INFO_SEPARATOR = '|||';

    /** ISO 3166-2, e.g. "DE-BY". */
    public const FIELD_SUBDIVISION_CODE = 'subdivisionCode';

    // --- keys of the answer -----------------------------------------------

    public const KEY_HTTP_CODE = 'httpCode';

    public const KEY_TRANSPORT_ERROR = 'transportError';

    public const KEY_RPC_ERROR = 'rpcError';

    public const KEY_STATUS = 'status';

    public const KEY_PREDICTIONS = 'predictions';

    /** The only HTTP code the service answers a valid request with. */
    public const HTTP_OK = '200';
}
