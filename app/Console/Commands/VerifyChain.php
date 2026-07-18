<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;

class VerifyChain extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'pos:verify-chain';

    /**
     * The console command description.
     */
    protected $description = 'Verify the cryptographic integrity of the sales ledger';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orders = Order::orderBy('sequence_number', 'asc')->get();

        if ($orders->isEmpty()) {
            $this->info("No orders found to verify.");
            return 0;
        }

        $this->info("🔍 Starting Audit: Verifying " . $orders->count() . " synced tickets...");

        // The first order of the system must chain to the standard 64-character zero string
        $previousHash = '0000000000000000000000000000000000000000000000000000000000000000';

        foreach ($orders as $order) {
            // 1. Verify the previous_hash link is intact
            if ($order->previous_hash !== $previousHash) {
                $this->error("\n❌ CRITICAL AUDIT FAILURE: CHAIN BROKEN!");
                $this->error("Broken at Ticket: #" . $order->sequence_number);
                $this->error("Expected Previous Hash: " . $previousHash);
                $this->error("Found Previous Hash:    " . $order->previous_hash);
                return 1;
            }

    

            // 🚀 Update this line to set timezone to UTC and format without milliseconds:
            $dataToHash = "{$order->sequence_number}|" . number_format($order->subtotal_excl_vat, 2, '.', '') . "|" . number_format($order->vat_amount, 2, '.', '') . "|" . number_format($order->total_incl_vat, 2, '.', '') . "|{$order->completed_at->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z')}|{$previousHash}";


            $calculatedHash = hash('sha256', $dataToHash);

            if ($order->hash !== $calculatedHash) {
                $this->error("\n❌ CRITICAL AUDIT FAILURE: DATA TAMPERING DETECTED!");
                $this->error("Tampered at Ticket: #" . $order->sequence_number);
                
                // 🚀 THE DIAGNOSTIC LOGS:
                $this->info("\n--- 🔍 SERVER RECONSTRUCTION DIAGNOSTIC ---");
                $this->info("Reconstructed String: ");
                $this->warn($dataToHash);
                $this->info("Recorded Hash inside DB:    " . $order->hash);
                $this->info("Recalculated Hash:          " . $calculatedHash);
                $this->info("-------------------------------------------\n");
                return 1;
            }

            // 3. Verify that the recorded hash matches the recalculated data (checks for price tampering)
            if ($order->hash !== $calculatedHash) {
                $this->error("\n❌ CRITICAL AUDIT FAILURE: DATA TAMPERING DETECTED!");
                $this->error("Tampered at Ticket: #" . $order->sequence_number);
                $this->error("The recorded hash does not match the recalculated dataset.");
                return 1;
            }

            // Move the chain forward
            $previousHash = $order->hash;
        }

        $this->info("\n✅ SUCCESS: The sales ledger is cryptographically secure and 100% untampered!");
        return 0;
    }
}
