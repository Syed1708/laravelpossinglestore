<?php

namespace App\Services\Inventory;

use App\Models\Order;
use App\Models\Ingredient;
use App\Models\Recipe;
use Illuminate\Support\Collection;

class StockService
{
    /**
     * Decrement ingredient stock for a collection of order items.
     *
     * @param array<int, array{product_id: int, quantity: int}>|Collection $items
     */
    public function decrementStockForItems(iterable $items): void
    {
        foreach ($items as $item) {
            $productId = is_array($item) ? ($item['product_id'] ?? null) : ($item->product_id ?? null);
            $quantity  = is_array($item) ? ($item['quantity'] ?? 1) : ($item->quantity ?? 1);

            if (!$productId || $quantity <= 0) {
                continue;
            }

            // Retrieve all recipes mapped to this product
            $recipes = Recipe::where('product_id', $productId)->get();

            foreach ($recipes as $recipe) {
                $deductQuantity = (float) $recipe->quantity * (int) $quantity;

                // Pessimistic lock prevents race conditions on inventory count
                $ingredient = Ingredient::lockForUpdate()->find($recipe->ingredient_id);
                if ($ingredient) {
                    $ingredient->decrement('stock_level', $deductQuantity);
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

        foreach ($order->items as $item) {
            if (!$item->product_id) {
                continue;
            }

            $recipes = Recipe::where('product_id', $item->product_id)->get();

            foreach ($recipes as $recipe) {
                $restoreQuantity = (float) $recipe->quantity * (int) $item->quantity;

                $ingredient = Ingredient::lockForUpdate()->find($recipe->ingredient_id);
                if ($ingredient) {
                    $ingredient->increment('stock_level', $restoreQuantity);
                }
            }
        }
    }
}