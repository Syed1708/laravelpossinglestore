<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'type',
        'value',
        'min_order_amount',
        'max_uses',
        'uses_count',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'value'            => 'float',
        'min_order_amount' => 'float',
        'max_uses'         => 'integer',
        'uses_count'       => 'integer',
        'is_active'        => 'boolean',
        'expires_at'       => 'datetime',
    ];

    /**
     * Check if coupon is currently valid for a given cart subtotal
     */
    public function isValidForAmount(float $subtotal): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && Carbon::now()->greaterThan($this->expires_at)) {
            return false;
        }

        if ($this->max_uses && $this->uses_count >= $this->max_uses) {
            return false;
        }

        if ($subtotal < $this->min_order_amount) {
            return false;
        }

        return true;
    }

    /**
     * Calculate discount amount for a given cart subtotal
     */
    public function calculateDiscount(float $subtotal): float
    {
        if (!$this->isValidForAmount($subtotal)) {
            return 0.00;
        }

        if ($this->type === 'percent') {
            return round(($subtotal * ($this->value / 100)), 2);
        }

        return round(min($subtotal, $this->value), 2);
    }
}