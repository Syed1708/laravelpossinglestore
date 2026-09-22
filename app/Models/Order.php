<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use HasinHayder\TyroDashboard\Concerns\HasCrud; // Import HasCrud
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasCrud; // Enable Tyro Automatic Read-Only Listing


    // 🚀 2. Automatically include 'local_created_at' in all JSON API responses
    protected $appends = [
        'local_created_at',
    ];

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

    /**
     * 🚀 Laravel 12 Standard: Dynamic method-based model casts
     */
    protected function casts(): array
    {
        return [
            'sequence_number'     => 'integer',
            'points_redeemed'     => 'integer',
            'points_earned'       => 'integer',
            'estimated_prep_time' => 'integer',
            'subtotal_excl_vat'   => 'decimal:2',
            'vat_amount'          => 'decimal:2',
            'total_incl_vat'      => 'decimal:2',
            'discount_amount'     => 'decimal:2',
            'completed_at'        => 'datetime',
            'estimated_ready_at'  => 'datetime',
        ];
    }


        /**
     * 🚀. Centralized Local Time Accessor:
     * Converts raw database UTC timestamp to the store's active local timezone (France, UK, or Bangladesh)
     */
    public function getLocalCreatedAtAttribute(): string
    {
        if (!$this->created_at) {
            return '';
        }

        return $this->created_at
            ->setTimezone(StoreSetting::timezone())
            ->format('d/m/Y H:i');
    }
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
