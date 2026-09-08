<?php

use App\Models\Order;
use App\Services\Fiscal\FiscalLedgerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->fiscalService = app(FiscalLedgerService::class);
});

it('exits successfully with zero code when no orders exist in the database', function () {
    $this->artisan('pos:verify-chain')
        ->expectsOutput('No orders found to verify.')
        ->assertExitCode(0);
});

it('successfully verifies an unbroken, cryptographically signed ledger chain', function () {
    $now = Carbon::now('UTC');

    // Ticket #1
    $sig1 = $this->fiscalService->generateSignature(1, 10.00, 1.00, 11.00, $now);
    Order::create([
        'uuid'              => (string) Str::uuid(),
        'sequence_number'   => 1,
        'subtotal_excl_vat' => 10.00,
        'vat_amount'        => 1.00,
        'total_incl_vat'    => 11.00,
        'hash'              => $sig1['hash'],
        'previous_hash'     => $sig1['previous_hash'],
        'completed_at'      => $sig1['completed_at'],
        'status'            => 'completed',
    ]);

    // Ticket #2 (Chained to Ticket #1)
    $sig2 = $this->fiscalService->generateSignature(2, 20.00, 2.00, 22.00, $now->copy()->addMinute());
    Order::create([
        'uuid'              => (string) Str::uuid(),
        'sequence_number'   => 2,
        'subtotal_excl_vat' => 20.00,
        'vat_amount'        => 2.00,
        'total_incl_vat'    => 22.00,
        'hash'              => $sig2['hash'],
        'previous_hash'     => $sig2['previous_hash'],
        'completed_at'      => $sig2['completed_at'],
        'status'            => 'completed',
    ]);

    $this->artisan('pos:verify-chain')
        ->expectsOutputToContain('Starting Audit: Verifying 2 synced tickets...')
        ->expectsOutputToContain('SUCCESS: The sales ledger is cryptographically secure and 100% untampered!')
        ->assertExitCode(0);
});

it('detects tampering when an order amount is illegally altered in the database', function () {
    $now = Carbon::now('UTC');

    $sig = $this->fiscalService->generateSignature(1, 10.00, 1.00, 11.00, $now);
    $order = Order::create([
        'uuid'              => (string) Str::uuid(),
        'sequence_number'   => 1,
        'subtotal_excl_vat' => 10.00,
        'vat_amount'        => 1.00,
        'total_incl_vat'    => 11.00,
        'hash'              => $sig['hash'],
        'previous_hash'     => $sig['previous_hash'],
        'completed_at'      => $sig['completed_at'],
        'status'            => 'completed',
    ]);

    // 🚨 ILLEGAL TAMPERING: Modify amount without re-computing the hash
    $order->update(['total_incl_vat' => 9.00]);

    $this->artisan('pos:verify-chain')
        ->expectsOutputToContain('CRITICAL AUDIT FAILURE: DATA TAMPERING DETECTED!')
        ->expectsOutputToContain('Tampered at Ticket: #1')
        ->assertExitCode(1);
});

it('detects a broken chain when a previous hash pointer is invalidated', function () {
    $now = Carbon::now('UTC');

    // Ticket #1
    $sig1 = $this->fiscalService->generateSignature(1, 10.00, 1.00, 11.00, $now);
    Order::create([
        'uuid'              => (string) Str::uuid(),
        'sequence_number'   => 1,
        'subtotal_excl_vat' => 10.00,
        'vat_amount'        => 1.00,
        'total_incl_vat'    => 11.00,
        'hash'              => $sig1['hash'],
        'previous_hash'     => $sig1['previous_hash'],
        'completed_at'      => $sig1['completed_at'],
        'status'            => 'completed',
    ]);

    // 🚀 FIX: Generate a valid 64-character SHA-256 string that breaks the chain
    $fakePreviousHash = hash('sha256', 'corrupted_previous_hash');
    $dataToHash = "2|20.00|2.00|22.00|{$now->format('Y-m-d\TH:i:s\Z')}|{$fakePreviousHash}";

    Order::create([
        'uuid'              => (string) Str::uuid(),
        'sequence_number'   => 2,
        'subtotal_excl_vat' => 20.00,
        'vat_amount'        => 2.00,
        'total_incl_vat'    => 22.00,
        'hash'              => hash('sha256', $dataToHash),
        'previous_hash'     => $fakePreviousHash, // 👈 Exactly 64 chars
        'completed_at'      => $now,
        'status'            => 'completed',
    ]);

    $this->artisan('pos:verify-chain')
        ->expectsOutputToContain('CRITICAL AUDIT FAILURE: CHAIN BROKEN!')
        ->expectsOutputToContain('Broken at Ticket: #2')
        ->assertExitCode(1);
});