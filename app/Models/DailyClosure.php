<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use HasinHayder\TyroDashboard\Concerns\HasCrud;

class DailyClosure extends Model
{
    use HasCrud; // Enable Tyro Automatic Read-Only Listing

    protected $fillable = [
        'z_number', 
        'total_ttc', 
        'total_ht', 
        'total_tva', 
        'vat_breakdown', 
        'payments_breakdown', 
        'hash', 
        'previous_hash', 
        'closed_at'
    ];

/**
     * 🚀 Laravel 12 Standard Casts
     */
    protected function casts(): array
    {
        return [
            'z_number'           => 'integer',
            'total_ttc'          => 'decimal:2',
            'total_ht'           => 'decimal:2',
            'total_tva'          => 'decimal:2',
            'vat_breakdown'      => 'array',
            'payments_breakdown' => 'array',
            'closed_at'          => 'datetime',
        ];
    }


    /**
     * Get the locked orders associated with this daily closure.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}