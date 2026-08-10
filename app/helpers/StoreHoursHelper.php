<?php

namespace App\Helpers;

use App\Models\StoreSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class StoreHoursHelper
{
    /**
     * 🚀 Checks if the restaurant is currently OPEN (Master Toggle + Operating Schedule)
     */
    public static function isOpen(): bool
    {
        $settings = StoreSetting::getSettings();

        // 1. Check Master Manual Override Toggle
        if (!$settings->is_store_open) {
            Log::info("Restaurant is manually forced closed by Admin.");
            return false;
        }

        // 2. Check if Current Time in Paris falls within Shift 1 or Shift 2
        $now = Carbon::now('Europe/Paris');
        return self::isTimeInSchedule($now->format('H:i'));
    }

    /**
     * 🚀 Checks if Online Orders can be accepted RIGHT NOW (Master Toggle + Online Toggle + Schedule)
     */
    public static function canAcceptOnlineOrders(): bool
    {
        $settings = StoreSetting::getSettings();

        if (!$settings->online_orders_enabled) {
            return false;
        }

        return self::isOpen();
    }

    /**
     * 🚀 Checks if Table Reservations are enabled
     */
    public static function canAcceptReservations(): bool
    {
        $settings = StoreSetting::getSettings();
        return (bool) $settings->reservations_enabled && (bool) $settings->is_store_open;
    }

    /**
     * 🚀 Checks if a specific time (e.g. "06:30" or "19:30") falls within shift 1 or shift 2
     */
    public static function isTimeInSchedule(string $time): bool
    {
        $settings = StoreSetting::getSettings();

        $checkTime  = Carbon::parse($time)->format('H:i');
        $s1Start    = Carbon::parse($settings->shift1_start)->format('H:i');
        $s1End      = Carbon::parse($settings->shift1_end)->format('H:i');
        $s2Start    = Carbon::parse($settings->shift2_start)->format('H:i');
        $s2End      = Carbon::parse($settings->shift2_end)->format('H:i');

        $inShift1 = self::isTimeInShift($checkTime, $s1Start, $s1End);
        $inShift2 = self::isTimeInShift($checkTime, $s2Start, $s2End);

        return $inShift1 || $inShift2;
    }

    /**
     * 🚀 Gets the dynamic closure message from database settings
     */
    public static function getClosedMessage(): string
    {
        $settings = StoreSetting::getSettings();
        return $settings->closed_message ?? 'Restaurant is currently closed for online ordering.';
    }

    /**
     * 🚀 Gets the dynamic schedule text string for display on Web/POS
     */
    public static function getScheduleText(): string
    {
        $settings = StoreSetting::getSettings();
        $s1Start = Carbon::parse($settings->shift1_start)->format('H:i');
        $s1End   = Carbon::parse($settings->shift1_end)->format('H:i');
        $s2Start = Carbon::parse($settings->shift2_start)->format('H:i');
        $s2End   = Carbon::parse($settings->shift2_end)->format('H:i');

        return "{$s1Start} - {$s1End} & {$s2Start} - {$s2End}";
    }

    /**
     * 🚀 OVERNIGHT SHIFT ENGINE:
     * Handles standard daytime shifts AND shifts that cross midnight
     */
    private static function isTimeInShift(string $currentTime, string $startTime, string $endTime): bool
    {
        if ($startTime <= $endTime) {
            return ($currentTime >= $startTime && $currentTime <= $endTime);
        }
        return ($currentTime >= $startTime || $currentTime <= $endTime);
    }
}