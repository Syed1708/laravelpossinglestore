<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    protected $fillable = [
        'employee_id',
        'period_start',
        'period_end',
        'hours_worked',
        'net_pay',
        'employer_charges',
        'total_employer_cost',
        'status',
        'paid_at',
        'expense_id',
        'notes',
    ];

    protected $casts = [
        'period_start'        => 'date',
        'period_end'          => 'date',
        'paid_at'             => 'datetime',
        'hours_worked'        => 'float',
        'net_pay'             => 'float',
        'employer_charges'    => 'float',
        'total_employer_cost' => 'float',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }
}
