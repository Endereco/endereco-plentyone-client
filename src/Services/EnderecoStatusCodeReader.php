<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Enums\EnderecoRpc;

/**
 * Reads the status codes out of the service's answer, dropping anything
 * that is not a string - the answer crosses a process boundary, so nothing
 * about its shape is guaranteed by PHP's type system.
 */
final class EnderecoStatusCodeReader
{
    /**
     * @param array<string, string|list<string>|list<array<string, string>>> $answer
     *
     * @return list<string>
     */
    public function read(array $answer): array
    {
        $value = $answer[EnderecoRpc::KEY_STATUS] ?? [];

        if (! is_array($value)) {
            return [];
        }

        $codes = [];

        foreach ($value as $code) {
            if (is_string($code)) {
                $codes[] = $code;
            }
        }

        return $codes;
    }
}
