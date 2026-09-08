<?php

namespace App\Services\Orders;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class SequenceService
{
    public const ORDER_SEQUENCE_KEY = 'order_sequence';

    /**
     * Atomically get the next sequential ticket number.
     * Uses a pessimistic row lock on the dedicated counter record to eliminate gap locks and deadlocks.
     */
    public function getNextSequenceNumber(string $key = self::ORDER_SEQUENCE_KEY): int
    {
        return DB::transaction(function () use ($key): int {
            // Pessimistic row lock on the single counter row
            $counter = DB::table('counters')
                ->where('key', $key)
                ->lockForUpdate()
                ->first();

            // Self-healing fallback if counter row does not exist yet
            if (! $counter) {
                $initialValue = (int) (Order::max('sequence_number') ?? 0) + 1;

                DB::table('counters')->insert([
                    'key'        => $key,
                    'value'      => $initialValue,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return $initialValue;
            }

            $nextValue = (int) $counter->value + 1;

            DB::table('counters')
                ->where('key', $key)
                ->update([
                    'value'      => $nextValue,
                    'updated_at' => now(),
                ]);

            return $nextValue;
        });
    }

    /**
     * Peek at the current sequence number without incrementing it (read-only).
     */
    public function getCurrentSequenceNumber(string $key = self::ORDER_SEQUENCE_KEY): int
    {
        $counter = DB::table('counters')->where('key', $key)->first();

        return $counter ? (int) $counter->value : (int) (Order::max('sequence_number') ?? 0);
    }
}