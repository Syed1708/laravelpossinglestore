<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use HasinHayder\TyroDashboard\Concerns\HasCrud; // Import HasCrud
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasCrud; // Enable Tyro Automatic Read-Only Listing

    protected $fillable = [
        'uuid', 
        'user_id', 
        'sequence_number', 
        'subtotal_excl_vat', 
        'vat_amount', 
        'total_incl_vat', 
        'hash', 
        'previous_hash', 
        'status', 
        'completed_at'
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

        public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}