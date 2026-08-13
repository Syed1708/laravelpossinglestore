<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    /**
     * Approve and disburse payroll, auto-posting total employer cost to Salaries Expense
     */
    public static function markPayrollAsPaid(Payroll $payroll): Payroll
    {
        return DB::transaction(function () use ($payroll) {
            $totalEmployerCost = (float) ($payroll->net_pay + $payroll->employer_charges);

            $payroll->update([
                'status'              => 'paid',
                'paid_at'             => Carbon::now(),
                'total_employer_cost' => $totalEmployerCost,
            ]);

            // Auto-Create Expense under "salaries" Category
            $salaryCategory = ExpenseCategory::where('code', 'salaries')->first();
            $employeeName = $payroll->employee ? "{$payroll->employee->first_name} {$payroll->employee->last_name}" : "Staff Member";

            $expense = Expense::create([
                'expense_category_id' => $salaryCategory ? $salaryCategory->id : null,
                'category'            => 'salaries',
                'description'         => "Payroll - {$employeeName} (" . Carbon::parse($payroll->period_start)->format('M Y') . ")",
                'amount'              => $totalEmployerCost,
                'payment_method'      => 'bank_transfer',
                'reference_type'      => 'payroll',
                'reference_id'        => $payroll->id,
                'paid_at'             => Carbon::now(),
            ]);

            $payroll->update(['expense_id' => $expense->id]);

            return $payroll;
        });
    }
}