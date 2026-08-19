<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Client extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'loyalty_points', // 🚀 Added loyalty_points to fillable
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
        // 🚀 Cast loyalty_points to an integer
    protected $casts = [
        'loyalty_points' => 'integer',
        'email_verified_at' => 'datetime',
    ];

    // 🚀 Default attribute value
    protected $attributes = [
        'loyalty_points' => 0,
    ];

    // Relationship: A Client has many Orders
    public function orders()
    {
        return $this->hasMany(Order::class, 'client_id');
    }
}