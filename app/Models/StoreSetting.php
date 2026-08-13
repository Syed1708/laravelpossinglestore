<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $fillable = [
        'country',
        'currency',
        'default_payroll_frequency',
        'is_store_open',
        'online_orders_enabled',
        'reservations_enabled',
        'shift1_start',
        'shift1_end',
        'shift2_start',
        'shift2_end',
        'closed_message',
    ];

    protected $casts = [
        'is_store_open'         => 'boolean',
        'online_orders_enabled' => 'boolean',
        'reservations_enabled'  => 'boolean',
    ];

    /**
     * 🚀 ALWAYS FETCHES THE NEWEST SETTINGS ROW
     */
    public static function getSettings(): self
    {
        return static::latest('id')->first() ?? static::create([
            'country'                   => 'FR',
            'currency'                  => 'EUR',
            'default_payroll_frequency' => 'monthly',
            'is_store_open'         => true,
            'online_orders_enabled' => true,
            'reservations_enabled'  => true,
            'shift1_start'          => '10:00:00',
            'shift1_end'            => '14:30:00',
            'shift2_start'          => '18:30:00',
            'shift2_end'            => '22:30:00',
            'closed_message'        => 'Restaurant is currently closed for reservations.',
        ]);
    }
}
