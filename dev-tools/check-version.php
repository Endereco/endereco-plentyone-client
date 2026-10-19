<?php

declare(strict_types=1);

/**
 * Fails if the agent string sent to the Endereco service does not carry the
 * version from plugin.json. The two are kept in sync by this check (part of
 * "composer qa").
 */

require __DIR__ . '/../src/Enums/EnderecoRpc.php';

use EnderecoPlentyOneClient\Enums\EnderecoRpc;

$plugin = json_decode((string) file_get_contents(__DIR__ . '/../plugin.json'), true);
$expected = 'Endereco PlentyONE Client v' . ($plugin['version'] ?? '');

if (EnderecoRpc::AGENT !== $expected) {
    fwrite(STDERR, sprintf(
        "EnderecoRpc::AGENT is \"%s\", but the version in plugin.json gives \"%s\".\n",
        EnderecoRpc::AGENT,
        $expected
    ));
    exit(1);
}

echo "Agent string matches plugin.json ($expected).\n";
