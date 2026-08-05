<?php

namespace App\Helpers;

use Carbon\Carbon;

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

        // Shift 1: 10:00 to 14:30
        $shift1Start = '10:00';
        $shift1End = '14:30';

        // Shift 2: 18:30 to 22:30
        $shift2Start = '18:30';
        $shift2End = '22:30';

        $isShift1 = ($currentTime >= $shift1Start && $currentTime <= $shift1End);
        $isShift2 = ($currentTime >= $shift2Start && $currentTime <= $shift2End);

        return $isShift1 || $isShift2;
    }

    public static function getScheduleText(): string
    {
        return "10:00 - 14:30 & 18:30 - 22:30";
    }
}