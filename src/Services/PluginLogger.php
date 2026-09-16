<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use Plenty\Log\Contracts\LoggerContract;
use Plenty\Plugin\Log\Loggable;

/**
 * The plugin's single entry point for logging.
 *
 * The code always comes from LogCode, and the order number is attached as
 * a searchable reference when given.
 *
 * Context values are scalars on purpose - nested arrays render as a tree in
 * the backend, awkward to read and to grep.
 */
final class PluginLogger
{
    use Loggable;

    /**
     * The reference type this plugin registers for a log entry's order id -
     * "endereco" prefixed because ReferenceContainer::add() is one flat,
     * system-wide list of type names, not namespaced per plugin.
     */
    public const ORDER_REFERENCE = 'enderecoOrderId';

    public function __construct(
        private PluginTranslator $translator,
    ) {
    }

    /**
     * Unlike info() and below, stored right away without the merchant
     * activating logging first - only for something that must be visible
     * before that's possible, chiefly a runOnBuild migration's first run.
     *
     * @param array<string, bool|float|int|string|null> $context
     */
    public function report(
        string $code,
        array $context = [],
        ?int $orderId = null
    ): void {
        $logger = $this->logger($code, $orderId);
        $logger->report($this->translator->fullKey($code), $context);
    }

    /** @param array<string, bool|float|int|string|null> $context */
    public function info(
        string $code,
        array $context = [],
        ?int $orderId = null
    ): void {
        $logger = $this->logger($code, $orderId);
        $logger->info($this->translator->fullKey($code), $context);
    }

    /** @param array<string, bool|float|int|string|null> $context */
    public function warning(
        string $code,
        array $context = [],
        ?int $orderId = null
    ): void {
        $logger = $this->logger($code, $orderId);
        $logger->warning($this->translator->fullKey($code), $context);
    }

    /** @param array<string, bool|float|int|string|null> $context */
    public function error(
        string $code,
        array $context = [],
        ?int $orderId = null
    ): void {
        $logger = $this->logger($code, $orderId);
        $logger->error($this->translator->fullKey($code), $context);
    }

    /**
     * A caught exception, logged twice on purpose: logException() keeps the
     * stack trace, the error entry keeps a readable reason.
     *
     * @param array<string, bool|float|int|string|null> $context
     */
    public function exception(
        string $code,
        \Throwable $exception,
        array $context = [],
        ?int $orderId = null
    ): void {
        $logger = $this->logger($code, $orderId);
        $logger->logException($exception);
        $context['reason'] = $exception->getMessage();
        $logger->error($this->translator->fullKey($code), $context);
    }

    private function logger(string $code, ?int $orderId): LoggerContract
    {
        $logger = $this->getLogger($code);

        if ($orderId === null) {
            return $logger;
        }

        return $logger->addReference(self::ORDER_REFERENCE, $orderId);
    }
}
