<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Table;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReservationController extends Controller
{
    /**
     * Render the interactive Hostess Floor Plan & Booking Manager
     */
    public function floorPlan(Request $request): View
    {
        $date = $request->input('date', Carbon::now('Europe/Paris')->toDateString());

        $tables = Table::where('is_active', true)->orderBy('table_number')->get();

        $reservations = Reservation::whereDate('reservation_date', $date)
            ->with(['table', 'client'])
            ->latest()
            ->get();

        // Calculate table IDs already booked or seated for this date
        $takenTableIds = $reservations
            ->filter(fn ($r) => !empty($r->table_id) && in_array($r->status, ['confirmed', 'seated']))
            ->pluck('table_id')
            ->unique()
            ->toArray();

        return view('admin.reservations.floor-plan', compact('tables', 'reservations', 'date', 'takenTableIds'));
    }
}