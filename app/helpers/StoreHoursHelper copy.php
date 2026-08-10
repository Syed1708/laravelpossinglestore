<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class StoreHoursHelper
{
    /**
     * Checks if Burger Palace Bordeaux is currently open for online ordering
     */
    public static function isOpen(): bool
    {
        // Current time in Bordeaux timezone (Europe/Paris)
        $now = Carbon::now('Europe/Paris');
        $currentTime = $now->format('H:i'); // e.g. "12:15" or "15:00"
        // 🚀 CORRECT LARAVEL LOGGING

        Log::info("Current time in Bordeaux: {$currentTime}");

        // Shift 1: Lunch (e.g. 10:00 to 14:30)
        $isShift1 = self::isTimeInShift($currentTime, '10:00', '14:30');

        // Shift 2: Dinner & Overnight (e.g. 18:30 to 06:00)
        $isShift2 = self::isTimeInShift($currentTime, '18:30', '06:00');

        return $isShift1 || $isShift2;
    }

        /**
     * 🚀 OVERNIGHT SHIFT ENGINE:
     * Handles standard daytime shifts AND shifts that cross midnight!
     */
    private static function isTimeInShift(string $currentTime, string $startTime, string $endTime): bool
    {
        // Standard shift within the same day (e.g., 10:00 to 14:30)
        if ($startTime <= $endTime) {
            return ($currentTime >= $startTime && $currentTime <= $endTime);
        }

        // Overnight shift crossing midnight (e.g., 18:30 to 06:00)
        return ($currentTime >= $startTime || $currentTime <= $endTime);
    }
    public static function getScheduleText(): string
    {
        return "10:00 - 14:30 & 18:30 - 06:30";
    }
}