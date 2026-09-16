<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Services;

use EnderecoPlentyOneClient\Models\QueueEntry;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;
use Plenty\Modules\Plugin\DataBase\Contracts\Query;

/**
 * Reads the plugin's queue table.
 */
final class QueueIndexReader
{
    private const COLUMN_ORDER_ID = 'orderId';

    private const COLUMN_STATE = 'state';

    private const COLUMN_ID = 'id';

    private const COLUMN_CREATED_AT = 'createdAt';

    public function __construct(
        private DataBase $database,
    ) {
    }

    /**
     * The oldest $limit order ids in the given state.
     *
     * @return list<int>
     */
    public function filteredOrderIds(string $state, int $limit): array
    {
        return $this->extract(
            $this->database->query(QueueEntry::class)->where(self::COLUMN_STATE, '=', $state),
            $limit
        );
    }

    /**
     * The oldest $limit order ids whose row was added before $cutoff -
     * regardless of state, see QueueSweepService.
     *
     * @return list<int>
     */
    public function ordersOlderThan(int $cutoff, int $limit): array
    {
        return $this->extract(
            $this->database->query(QueueEntry::class)->where(self::COLUMN_CREATED_AT, '<', $cutoff),
            $limit
        );
    }

    /**
     * The oldest $limit order ids in the table, regardless of state - see
     * QueueSweepService.
     *
     * @return list<int>
     */
    public function allOrderIds(int $limit): array
    {
        return $this->extract($this->database->query(QueueEntry::class), $limit);
    }

    /** The row for one order, or null when it is not queued. */
    public function entry(int $orderId): ?QueueEntry
    {
        $rows = $this->database->query(QueueEntry::class)
            ->where(self::COLUMN_ORDER_ID, '=', $orderId)
            ->limit(1)
            ->get();

        $row = $rows[0] ?? null;

        return $row instanceof QueueEntry ? $row : null;
    }

    /**
     * How many rows currently sit in the given stage.
     */
    public function count(string $state): int
    {
        return count(
            $this->database->query(QueueEntry::class)
                ->where(self::COLUMN_STATE, '=', $state)
                ->get()
        );
    }

    /**
     * Runs the query, oldest first, and pulls out just the order ids.
     *
     * @return list<int>
     */
    private function extract(Query $query, int $limit): array
    {
        $rows = $query->orderBy(self::COLUMN_ID)->limit($limit)->get();

        $orderIds = [];

        foreach ($rows as $row) {
            if ($row instanceof QueueEntry) {
                $orderIds[] = $row->orderId;
            }
        }

        return $orderIds;
    }
}
