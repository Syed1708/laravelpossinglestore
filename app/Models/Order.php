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
        'client_id',
        'customer_name',
        'customer_phone',
        'order_type',
        'sequence_number',
        
        // 🚀 MUST BE LISTED HERE SO ELOQUENT DOES NOT DROP THEM!
        'coupon_code',
        'discount_amount',
        'points_redeemed',
        'points_earned',

        'subtotal_excl_vat',
        'vat_amount',
        'total_incl_vat',
        'hash',
        'previous_hash',
        'completed_at',
        'preparation_status',
        'estimated_prep_time',
        'estimated_ready_at',
        'status',
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
