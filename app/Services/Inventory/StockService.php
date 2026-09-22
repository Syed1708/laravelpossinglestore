<?php

namespace App\Services\Inventory;

use App\Models\Ingredient;
use App\Models\Order;
use App\Models\Recipe;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class StockService
{
    /**
     * Decrement ingredient stock for a collection of order items.
     *
     * Aggregates quantities and locks ingredients in strict ascending ID order
     * to completely eliminate InnoDB circular deadlocks and N+1 queries.
     *
     * @param  array<int, array{product_id: int, quantity: int}>|Collection  $items
     */
    public function decrementStockForItems(iterable $items): void
    {
        // 1. Map and consolidate product quantities from order items
        $productQuantities = [];
        foreach ($items as $item) {
            $productId = is_array($item) ? ($item['product_id'] ?? null) : ($item->product_id ?? null);
            $quantity = is_array($item) ? ($item['quantity'] ?? 1) : ($item->quantity ?? 1);

            if ($productId && $quantity > 0) {
                $productQuantities[$productId] = ($productQuantities[$productId] ?? 0) + (int) $quantity;
            }
        }

        if (empty($productQuantities)) {
            return;
        }

        // 2. Fetch all relevant recipes in a SINGLE query
        $recipes = Recipe::whereIn('product_id', array_keys($productQuantities))->get();

        if ($recipes->isEmpty()) {
            return;
        }

        // 3. Aggregate total deductions per ingredient
        $deductionsByIngredient = [];
        foreach ($recipes as $recipe) {
            $orderedProductQty = $productQuantities[$recipe->product_id] ?? 0;
            $deductAmount = (float) $recipe->quantity * $orderedProductQty;

            if ($deductAmount > 0) {
                $deductionsByIngredient[$recipe->ingredient_id] = ($deductionsByIngredient[$recipe->ingredient_id] ?? 0) + $deductAmount;
            }
        }

        if (empty($deductionsByIngredient)) {
            return;
        }

        // 4. 🛡️ DEADLOCK PREVENTION: Lock rows strictly in ascending numeric ID order
        ksort($deductionsByIngredient);

        // 5. Fetch and lock all needed ingredients in a single query
        $ingredientIds = array_keys($deductionsByIngredient);
        $ingredients = Ingredient::whereIn('id', $ingredientIds)
            ->lockForUpdate()
            ->orderBy('id', 'asc')
            ->get()
            ->keyBy('id');

        // 6. Apply deductions
        foreach ($deductionsByIngredient as $ingredientId => $deductQuantity) {
            if ($ingredient = $ingredients->get($ingredientId)) {
                $ingredient->decrement('stock_level', $deductQuantity);

                // Log low stock alert if threshold reached
                if ($ingredient->stock_level <= $ingredient->alert_level) {
                    Log::warning("⚠️ Low stock alert for [{$ingredient->name}] (ID: {$ingredient->id}). Current: {$ingredient->stock_level} {$ingredient->unit} (Threshold: {$ingredient->alert_level})");
                }
            }
        }
    }

    /**
     * Restore ingredient stock if an order is cancelled or refunded.
     */
    public function restoreStockForOrder(Order $order): void
    {
        $order->loadMissing('items');

        // 1. Consolidate product quantities
        $productQuantities = [];
        foreach ($order->items as $item) {
            if ($item->product_id && $item->quantity != 0) {
                $qty = abs((int) $item->quantity);
                $productQuantities[$item->product_id] = ($productQuantities[$item->product_id] ?? 0) + $qty;
            }
        }

        if (empty($productQuantities)) {
            return;
        }

        // 2. Fetch all recipes in a single query
        $recipes = Recipe::whereIn('product_id', array_keys($productQuantities))->get();

        if ($recipes->isEmpty()) {
            return;
        }

        // 3. Aggregate total restorations per ingredient
        $restorationsByIngredient = [];
        foreach ($recipes as $recipe) {
            $orderedProductQty = $productQuantities[$recipe->product_id] ?? 0;
            $restoreAmount = (float) $recipe->quantity * $orderedProductQty;

            if ($restoreAmount > 0) {
                $restorationsByIngredient[$recipe->ingredient_id] = ($restorationsByIngredient[$recipe->ingredient_id] ?? 0) + $restoreAmount;
            }
        }

        if (empty($restorationsByIngredient)) {
            return;
        }

        // 4. 🛡️ Sort ascending to prevent deadlocks during concurrent cancels
        ksort($restorationsByIngredient);

        // 5. Fetch, lock, and restore in a single query
        $ingredientIds = array_keys($restorationsByIngredient);
        $ingredients = Ingredient::whereIn('id', $ingredientIds)
            ->lockForUpdate()
            ->orderBy('id', 'asc')
            ->get()
            ->keyBy('id');

        foreach ($restorationsByIngredient as $ingredientId => $restoreQuantity) {
            if ($ingredient = $ingredients->get($ingredientId)) {
                $ingredient->increment('stock_level', $restoreQuantity);
            }
        }
    }
}
