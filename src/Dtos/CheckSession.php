<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Dtos;

/**
 * Identifies one address check to the service, sent as the x-transaction-id
 * header and, redundantly, as an RPC parameter.
 *
 * A retry after a failed call gets a new session id, since nothing was
 * delivered under the old one.
 */
final class CheckSession
{
    public function __construct(
        private string $id,
    ) {
    }

    public static function create(): self
    {
        return pluginApp(self::class, [self::uuid4()]);
    }

    public function id(): string
    {
        return $this->id;
    }

    private static function uuid4(): string
    {
        return sprintf(
            '%04x%04x-%04x-4%03x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff),
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
        );
    }
}
