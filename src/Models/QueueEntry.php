<?php

declare(strict_types=1);

namespace EnderecoPlentyOneClient\Models;

use Plenty\Modules\Plugin\DataBase\Contracts\Model;

/**
 * A search index for orders waiting to be checked - PlentyONE cannot search
 * orders by property or tag, so this table stands in for that.
 */
final class QueueEntry extends Model
{
    /** Insertion order - gives the queue its FIFO order. */
    public int $id = 0;

    /** The order this row is about. */
    public int $orderId = 0;

    /** Copy of the order's endereco_process_state property. */
    public string $state = '';

    /** Unix timestamp of when this row was added - see QueueSweepService. */
    public int $createdAt = 0;

    public function getTableName(): string
    {
        return 'EnderecoPlentyOneClient::queue_entry';
    }
}
