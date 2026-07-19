<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use HasinHayder\TyroDashboard\Concerns\HasCrud;

class DailyClosure extends Model
{
    use HasCrud; // Enable Tyro Automatic Read-Only Listing

    protected $fillable = [
        'z_number', 'total_ttc', 'total_ht', 'total_tva', 
        'vat_breakdown', 'payments_breakdown', 'hash', 
        'previous_hash', 'closed_at'
    ];

    protected $casts = [
        'vat_breakdown' => 'array',
        'payments_breakdown' => 'array',
        'closed_at' => 'datetime'
    ];

    /**
     * Get the locked orders associated with this daily closure.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}