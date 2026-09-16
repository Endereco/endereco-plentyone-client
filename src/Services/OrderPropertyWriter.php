<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Enums\LogCode;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;

/**
 * Writes the plugin's own properties onto an order.
 *
 * A missing type is a hard stop, not a partial write - it would mean the
 * plugin was never deployed properly, and writing half a state would leave
 * the order stuck. PlentyONE merges the list with what the order already has.
 */
final class OrderPropertyWriter
{
    public function __construct(
        private OrderRepositoryContract $orders,
        private OrderPropertyTypeRegistry $registry,
        private PluginLogger $logger,
    ) {
    }

    /** @param array<string, string> $values name => value */
    public function write(int $orderId, array $values): bool
    {
        $payload = $this->payload($orderId, $values);

        if ($payload === null) {
            return false;
        }

        try {
            $this->orders->update($orderId, ['properties' => $payload]);
        } catch (\Throwable $exception) {
            $this->logger->exception(
                LogCode::PROPERTY_WRITE_FAILED,
                $exception,
                ['properties' => implode(', ', array_keys($values))],
                $orderId,
            );

            return false;
        }

        return true;
    }

    /**
     * The property list for update(), or null when a type is missing.
     *
     * @param array<string, string> $values name => value
     *
     * @return list<array<string, int|string>>|null
     */
    private function payload(int $orderId, array $values): ?array
    {
        $typeIds = $this->registry->typeIds();
        $payload = [];

        foreach ($values as $name => $value) {
            $typeId = $typeIds[$name] ?? null;

            if ($typeId === null) {
                $this->logger->error(
                    LogCode::PROPERTY_TYPE_MISSING,
                    ['property' => $name],
                    $orderId
                );

                return null;
            }

            $payload[] = ['typeId' => $typeId, 'value' => $value];
        }

        return $payload;
    }
}
