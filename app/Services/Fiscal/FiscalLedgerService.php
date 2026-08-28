<?php

namespace App\Services\Fiscal;

use App\Models\Order;
use Carbon\Carbon;

class FiscalLedgerService
{
    public const INITIAL_PREVIOUS_HASH = '0000000000000000000000000000000000000000000000000000000000000000';

    /**
     * Generates the previous hash and the new SHA-256 cryptographic hash for an order.
     *
     * @return array{previous_hash: string, hash: string, completed_at: Carbon}
     */
    public function generateSignature(
        int $sequenceNumber,
        float $subtotalExclVat,
        float $vatAmount,
        float $totalInclVat,
        ?Carbon $completedAt = null
    ): array {
        $completedAt = $completedAt ?? Carbon::now();

        // Lock the previous valid hashed order to guarantee linear chaining
        $lastHashOrder = Order::whereNotNull('hash')
            ->where('hash', '!=', '')
            ->orderBy('sequence_number', 'desc')
            ->lockForUpdate()
            ->first();

        $previousHash = ($lastHashOrder && !empty($lastHashOrder->hash))
            ? $lastHashOrder->hash
            : self::INITIAL_PREVIOUS_HASH;

        // NF525 standardized signature payload string:
        // Format: sequence_number|subtotal_ht|vat_amount|total_ttc|UTC_timestamp|previous_hash
        $dataToHash = "{$sequenceNumber}|"
            . number_format($subtotalExclVat, 2, '.', '') . '|'
            . number_format($vatAmount, 2, '.', '') . '|'
            . number_format($totalInclVat, 2, '.', '') . '|'
            . $completedAt->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z') . '|'
            . $previousHash;

        $hash = hash('sha256', $dataToHash);

        return [
            'previous_hash' => $previousHash,
            'hash'          => $hash,
            'completed_at'  => $completedAt,
        ];
    }
}