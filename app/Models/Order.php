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
        'payment_intent_id',
        'user_id',
        'client_id',
        'order_type',     // 🚀 Added (dine_in, takeaway, click_and_collect)
        'customer_name',  // 🚀 Added
        'customer_phone', // 🚀 Added
        'sequence_number',
        'subtotal_excl_vat',
        'vat_amount',
        'total_incl_vat',
        'hash',
        'previous_hash',
        'status',
        'completed_at',
        'preparation_status', // 'not_accepted', 'accepted', 'preparing', 'ready', 'delivered', 'cancelled'
        'estimated_prep_time', // 🚀 Added (minutes)
        'estimated_ready_at',   // 🚀 Added (datetime)
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
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
