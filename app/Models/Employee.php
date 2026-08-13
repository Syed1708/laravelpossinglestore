<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employee extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'job_title',
        'contract_type',
        'contract_hours',
        'pay_type',
        'base_rate',
        'iban_bank_details',
        'hire_date',
        'is_active',
    ];

    protected $casts = [
        'contract_hours' => 'float',
        'base_rate'      => 'float',
        'hire_date'      => 'date',
        'is_active'      => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }
}
