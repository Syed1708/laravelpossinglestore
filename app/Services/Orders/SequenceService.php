<?php

namespace App\Services\Orders;

use App\Models\Order;

class SequenceService
{
    /**
     * Atomically get the next sequential ticket number with pessimistic row locking.
     */
    public function getNextSequenceNumber(): int
    {
        $lastSeqOrder = Order::orderBy('sequence_number', 'desc')
            ->lockForUpdate()
            ->first();

        return $lastSeqOrder ? ($lastSeqOrder->sequence_number + 1) : 1;
    }
}