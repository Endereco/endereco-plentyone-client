<?php

/**
 * Calls one JSON-RPC method of the Endereco service.
 *
 * Runs in the plentymarkets SDK sandbox via LibraryCallContract, not the
 * plugin container - the only place a plugin may open an HTTP connection,
 * since curl does not exist in the plugin runtime. Never throws; a failure
 * comes back as a field for the plugin side to turn into an exception.
 *
 * Params: endpoint, apiKey, agent, sessionId, method, params.
 * Returns: httpCode, transportError, rpcError, status (list of codes),
 * predictions (list of address suggestions, all values strings).
 */

$endpoint = (string) SdkRestApi::getParam('endpoint');
$apiKey = (string) SdkRestApi::getParam('apiKey');
$agent = (string) SdkRestApi::getParam('agent');
$sessionId = (string) SdkRestApi::getParam('sessionId');
$method = (string) SdkRestApi::getParam('method');
$params = SdkRestApi::getParam('params');

$answer = [
    'httpCode' => '0',
    'transportError' => '',
    'rpcError' => '',
    'status' => [],
    'predictions' => [],
];

$payload = json_encode([
    'jsonrpc' => '2.0',
    'id' => 1,
    'method' => $method,
    'params' => is_array($params) ? $params : [],
]);

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $endpoint,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => $payload,
    // A cron run has fifty orders to get through, so a hanging service must
    // not eat the whole run.
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-Auth-Key: ' . $apiKey,
        // The session, in the header as well as in the params. The service
        // wants the redundancy, and this header is what ties every call
        // about one address together.
        'X-Transaction-Id: ' . $sessionId,
        'X-Agent: ' . $agent,
    ],
]);

$body = curl_exec($curl);
$transportError = (string) curl_error($curl);
$answer['httpCode'] = (string) curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($transportError !== '') {
    $answer['transportError'] = $transportError;

    return $answer;
}

$decoded = json_decode(is_string($body) ? $body : '', true);

if (!is_array($decoded)) {
    // Happens with a wrong endpoint path: the web server answers with an
    // HTML error page instead of JSON.
    $answer['rpcError'] = 'The service did not answer with JSON.';

    return $answer;
}

// A wrong, outdated or inactive API key arrives here, together with a
// non-200 HTTP code.
if (isset($decoded['error'])) {
    $error = $decoded['error'];
    $answer['rpcError'] = is_array($error) && isset($error['message'])
        ? (string) $error['message']
        : 'The service reported an error without a message.';

    return $answer;
}

$result = isset($decoded['result']) && is_array($decoded['result'])
    ? $decoded['result']
    : [];

if (isset($result['status']) && is_array($result['status'])) {
    foreach ($result['status'] as $code) {
        if (is_string($code)) {
            $answer['status'][] = $code;
        }
    }
}

if (isset($result['predictions']) && is_array($result['predictions'])) {
    foreach ($result['predictions'] as $prediction) {
        if (!is_array($prediction)) {
            continue;
        }

        $fields = [];

        foreach ($prediction as $field => $value) {
            if (is_string($value) || is_int($value)) {
                $fields[(string) $field] = (string) $value;
            }
        }

        $answer['predictions'][] = $fields;
    }
}

return $answer;
