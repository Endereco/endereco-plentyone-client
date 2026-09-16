<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Enums\EnderecoRpc;

/**
 * Reads the address suggestions out of the service's answer.
 *
 * single() only ever hands out a suggestion when there is exactly one.
 * all() returns every candidate regardless of count.
 */
final class EnderecoPredictionReader
{
    /**
     * The single suggestion, or null when there is none or more than one.
     *
     * @param array<string, string|list<string>|list<array<string, string>>> $answer
     *
     * @return array<string, string>|null
     */
    public function single(array $answer): ?array
    {
        $value = $answer[EnderecoRpc::KEY_PREDICTIONS] ?? [];

        if (! is_array($value) || count($value) !== 1) {
            return null;
        }

        $prediction = reset($value);

        return is_array($prediction) ? $prediction : null;
    }

    /**
     * Every suggestion the service returned, whatever the count - used to
     * show them in a comment when there is more than one.
     *
     * @param array<string, string|list<string>|list<array<string, string>>> $answer
     *
     * @return list<array<string, string>>
     */
    public function all(array $answer): array
    {
        $value = $answer[EnderecoRpc::KEY_PREDICTIONS] ?? [];

        if (! is_array($value)) {
            return [];
        }

        $predictions = [];

        foreach ($value as $candidate) {
            if (is_array($candidate)) {
                $predictions[] = $candidate;
            }
        }

        return $predictions;
    }
}
