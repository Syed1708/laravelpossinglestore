<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'table_id',
        'order_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'guest_count',
        'reservation_date',
        'reservation_time',
        'status',
        'source',
        'special_notes',
    ];

    protected $casts = [
        'guest_count' => 'integer',
        'reservation_date' => 'date:Y-m-d', // 👈 Fixes empty date input on edit!

    ];

       /**
     * 🚀 Accessor Fallback: Guarantees string YYYY-MM-DD when accessed in Blade views
     */
    public function getReservationDateAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : null;
    }
    /**
     * Registered Web Customer (Optional)
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * Assigned Restaurant Table
     */
    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'table_id');
    }

    /**
     * Linked POS Order (When guests are seated)
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
