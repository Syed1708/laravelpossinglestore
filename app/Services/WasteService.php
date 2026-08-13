<?php

namespace App\Services;

use App\Models\Waste;
use App\Models\Ingredient;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Helpers\UnitConverter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WasteService
{
    /**
     * Log food waste, decrement ingredient stock, and auto-post a waste_loss expense
     */
    public static function logIngredientWaste(array $data, int $userId = null): Waste
    {
        return DB::transaction(function () use ($data, $userId) {
            $ingredient = Ingredient::findOrFail($data['ingredient_id']);
            
            // 1. Convert input quantity to base storage unit (grams/ml/units)
            $qtyInBaseUnit = UnitConverter::toBaseUnit((float) $data['quantity_wasted'], $data['unit']);
            $costPerUnit   = (float) ($data['cost_per_unit'] ?? 0);
            $totalLoss     = $qtyInBaseUnit * $costPerUnit;

            // 2. Decrement ingredient stock
            $ingredient->decrement('stock_level', $qtyInBaseUnit);

            // 3. Create Waste Record
            $waste = Waste::create([
                'ingredient_id'     => $ingredient->id,
                'quantity_wasted'   => $qtyInBaseUnit,
                'unit'              => $ingredient->unit, // Base unit (g, ml, unit)
                'cost_per_unit'     => $costPerUnit,
                'total_loss_amount' => $totalLoss,
                'reason'            => $data['reason'] ?? 'spoiled_expired',
                'logged_by_user_id' => $userId ?? auth()->id(),
                'notes'             => $data['notes'] ?? null,
            ]);

            // 4. Auto-Create Expense in "waste_loss" Category
            $wasteCategory = ExpenseCategory::where('code', 'waste_loss')->first();

            $expense = Expense::create([
                'expense_category_id' => $wasteCategory ? $wasteCategory->id : null,
                'category'            => 'waste_loss',
                'description'         => "Food Waste: {$ingredient->name} ({$data['quantity_wasted']} {$data['unit']})",
                'amount'              => $totalLoss,
                'payment_method'      => 'other',
                'reference_type'      => 'waste',
                'reference_id'        => $waste->id,
                'paid_at'             => Carbon::now(),
            ]);

            $waste->update(['expense_id' => $expense->id]);

            return $waste;
        });
    }
}