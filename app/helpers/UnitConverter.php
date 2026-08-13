<?php

namespace App\Helpers;

class UnitConverter
{
    /**
     * Converts any input unit (kg, L, cl) to the database Base Unit (g, ml, unit)
     */
    public static function toBaseUnit(float $quantity, string $unit): float
    {
        return match (strtolower($unit)) {
            'kg' => $quantity * 1000, // 10 kg -> 10,000 g
            'l'  => $quantity * 1000, // 5 Liters -> 5,000 ml
            'cl' => $quantity * 10,   // 50 cl -> 500 ml
            default => $quantity,     // 'g', 'ml', 'unit' already in base unit
        };
    }

    /**
     * Formats database Base Unit for human display on Tyro Dashboard / Admin
     */
    public static function formatForDisplay(float $baseQuantity, string $unit): string
    {
        $unit = strtolower($unit);

        // Convert Grams to Kilograms if >= 1000g
        if ($unit === 'g' && $baseQuantity >= 1000) {
            return number_format($baseQuantity / 1000, 2) . ' kg';
        }

        // Convert Milliliters to Liters if >= 1000ml
        if ($unit === 'ml' && $baseQuantity >= 1000) {
            return number_format($baseQuantity / 1000, 2) . ' L';
        }

        return number_format($baseQuantity, 0) . ' ' . $unit;
    }
}