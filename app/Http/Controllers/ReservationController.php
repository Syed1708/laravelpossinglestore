<?php
namespace App\Http\Controllers;



use App\Models\Reservation;
use App\Models\Table;
use App\Events\ReservationUpdated;
use App\Helpers\StoreHoursHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ReservationController extends Controller
{

    /**
     * Render the interactive Hostess Floor Plan & Booking Manager
     */
    public function floorPlan(Request $request)
    {
        $date = $request->input('date', Carbon::now('Europe/Paris')->toDateString());

        $tables = Table::where('is_active', true)->orderBy('table_number')->get();

        $reservations = Reservation::whereDate('reservation_date', $date)
            ->with(['table', 'client'])
            ->latest()
            ->get();

        // 🚀 CALCULATE TABLE IDs ALREADY BOOKED OR SEATED FOR THIS DATE
        $takenTableIds = $reservations
            ->filter(fn($r) => !empty($r->table_id) && in_array($r->status, ['confirmed', 'seated']))
            ->pluck('table_id')
            ->unique()
            ->toArray();

        return view('admin.reservations.floor-plan', compact('tables', 'reservations', 'date', 'takenTableIds'));
    }
    /**
     * 1. Public API: Check table availability for a specific date, time, and guest count
     */
    public function checkAvailability(Request $request)
    {
        $validated = $request->validate([
            'date'   => 'required|date',
            'time'   => 'required|string', // e.g. "19:30"
            'guests' => 'required|integer|min:1|max:30',
            'zone'   => 'nullable|string', // 'indoor', 'terrace', 'vip'
        ]);

        $date = $validated['date'];
        $time = $validated['time'];
        $guests = (int) $validated['guests'];
        $zone = $validated['zone'] ?? null;

        // Query active tables meeting minimum guest capacity
        $query = Table::where('is_active', true)
            ->where('capacity', '>=', $guests);

        if ($zone) {
            $query->where('zone', $zone);
        }

        // Exclude tables already reserved within a 2-hour window on the same day
        $bookingTime = Carbon::parse("{$date} {$time}");
        $windowStart = (clone $bookingTime)->subHours(1)->format('H:i:s');
        $windowEnd = (clone $bookingTime)->addHours(1)->format('H:i:s');

        $availableTables = $query->whereDoesntHave('reservations', function ($q) use ($date, $windowStart, $windowEnd) {
            $q->where('reservation_date', $date)
                ->whereIn('status', ['confirmed', 'seated'])
                ->whereBetween('reservation_time', [$windowStart, $windowEnd]);
        })->get();

        return response()->json([
            'is_available'     => $availableTables->isNotEmpty(),
            'available_tables' => $availableTables,
        ]);
    }


    /**
     * 🚀 TABLE CONFLICT CHECKER:
     * Checks if a table is already booked within a 2-hour window on the same date
     */
    private function isTableAlreadyReserved($tableId, $date, $time, $excludeReservationId = null): bool
    {
        if (!$tableId) {
            return false; // No table assigned yet
        }

        // Define a 2-hour reservation window (90 mins before & 90 mins after)
        $bookingTime = Carbon::parse("{$date} {$time}");
        $windowStart = (clone $bookingTime)->subMinutes(90)->format('H:i:s');
        $windowEnd   = (clone $bookingTime)->addMinutes(90)->format('H:i:s');

        $query = Reservation::where('table_id', $tableId)
            ->where('reservation_date', $date)
            ->whereIn('status', ['confirmed', 'seated'])
            ->whereBetween('reservation_time', [$windowStart, $windowEnd]);

        if ($excludeReservationId) {
            $query->where('id', '!=', $excludeReservationId);
        }

        return $query->exists();
    }
    /**
     * 2. Public API: Customer Web Self-Booking (/reservation)
     */
    public function storeOnline(Request $request)
    {

        // 🛑 REQUIRE CUSTOMER LOGIN: Block unauthenticated web bookings
        $clientId = auth('sanctum')->id() ?? $request->user('sanctum')?->id;
        if (!$clientId) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required. Please sign in to reserve a table.'
            ], 401);
        }
        // // 🛑 Check 1: Global Settings Toggle
        // if (!StoreHoursHelper::canAcceptReservations()) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Table reservations are currently closed by administration.'
        //     ], 422);
        // }

        $validated = $request->validate([
            'customer_name'    => 'required|string|max:255',
            'customer_phone'   => 'required|string|max:50',
            'customer_email'   => 'nullable|email|max:255',
            'guest_count'      => 'required|integer|min:1',
            'reservation_date' => 'required|date|after_or_equal:today',
            'reservation_time' => 'required|string',
            'special_notes'    => 'nullable|string|max:500',
        ]);

        $todayInParis = Carbon::now('Europe/Paris')->toDateString();
        $currentTimeInParis = Carbon::now('Europe/Paris')->format('H:i');

        // 🛑 CHECK 2: Block Past Dates
        if ($validated['reservation_date'] < $todayInParis) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot create a reservation for a past date.'
            ], 422);
        }

        // 🛑 CHECK 3: Block Past Time (If booking for today)
        if ($validated['reservation_date'] === $todayInParis && $validated['reservation_time'] < $currentTimeInParis) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot create a reservation for a past time today.'
            ], 422);
        }

        // 🛑 CHECK 4: Block Out-of-Schedule Times (e.g. 06:30)
        if (!StoreHoursHelper::isTimeInSchedule($validated['reservation_time'])) {
            return response()->json([
                'success' => false,
                'message' => 'Booking time (' . $validated['reservation_time'] . ') is outside opening hours (' . StoreHoursHelper::getScheduleText() . ').'
            ], 422);
        }


        // 🚀 AUTO-ASSIGN UNRESERVED TABLE THAT FITS GUEST COUNT
        $bookingTime = Carbon::parse("{$validated['reservation_date']} {$validated['reservation_time']}");
        $windowStart = (clone $bookingTime)->subMinutes(90)->format('H:i:s');
        $windowEnd   = (clone $bookingTime)->addMinutes(90)->format('H:i:s');

        $availableTable = Table::where('is_active', true)
            ->where('capacity', '>=', $validated['guest_count'])
            ->whereDoesntHave('reservations', function ($q) use ($validated, $windowStart, $windowEnd) {
                $q->where('reservation_date', $validated['reservation_date'])
                    ->whereIn('status', ['confirmed', 'seated'])
                    ->whereBetween('reservation_time', [$windowStart, $windowEnd]);
            })
            ->first();

        if (!$availableTable) {
            return response()->json([
                'success' => false,
                'message' => "No tables available for {$validated['guest_count']} guests on {$validated['reservation_date']} at {$validated['reservation_time']}. Please select another time slot."
            ], 422);
        }

        $clientId = auth('sanctum')->id() ?? $request->input('client_id');



        $reservation = Reservation::create([
            'client_id'        => $clientId,
            'table_id'         => $availableTable->id,
            'customer_name'    => $validated['customer_name'],
            'customer_phone'   => $validated['customer_phone'],
            'customer_email'   => $validated['customer_email'] ?? null,
            'guest_count'      => $validated['guest_count'],
            'reservation_date' => $validated['reservation_date'],
            'reservation_time' => $validated['reservation_time'],
            'status'           => 'confirmed',
            'source'           => 'online',
            'special_notes'    => $validated['special_notes'] ?? null,
        ]);

        // Real-time broadcast to Web POS Hostess & Tyro Admin
        try {
            event(new ReservationUpdated('created', $reservation));
        } catch (\Exception $e) {
            Log::warning('WebSocket broadcast for reservation failed: ' . $e->getMessage());
        }

        return response()->json([
            'success'     => true,
            'message'     => 'Table reservation confirmed!',
            'reservation' => $reservation->load('table'),
        ], 201);
    }

    /**
     * 3. Staff API: Cashier Phone Booking (/pos or /admin)
     */
    public function storePhoneBooking(Request $request)
    {
        // 🛑 CHECK 1: Global Setting Toggle Check
        if (!StoreHoursHelper::canAcceptReservations()) {
            return response()->json([
                'success' => false,
                'message' => StoreHoursHelper::getClosedMessage()
            ], 422);
        }

        $validated = $request->validate([
            'customer_name'    => 'required|string|max:255',
            'customer_phone'   => 'required|string|max:50',
            'guest_count'      => 'required|integer|min:1',
            'reservation_date' => 'required|date',
            'reservation_time' => 'required|string',
            'table_id'         => 'nullable|exists:tables,id',
            'special_notes'    => 'nullable|string|max:500',
        ]);

        $todayInParis = Carbon::now('Europe/Paris')->toDateString();
        $currentTimeInParis = Carbon::now('Europe/Paris')->format('H:i');

        // 🛑 Block Past Dates
        if ($validated['reservation_date'] < $todayInParis) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot create a reservation for a past date.'
            ], 422);
        }

        // 🛑 Block Past Time Today
        if ($validated['reservation_date'] === $todayInParis && $validated['reservation_time'] < $currentTimeInParis) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot create a reservation for a past time today.'
            ], 422);
        }

        // 🛑 Block Out-of-Schedule Times
        if (!StoreHoursHelper::isTimeInSchedule($validated['reservation_time'])) {
            return response()->json([
                'success' => false,
                'message' => 'Time (' . $validated['reservation_time'] . ') is outside schedule (' . StoreHoursHelper::getScheduleText() . ').'
            ], 422);
        }


        // 🛑 TABLE DOUBLE-BOOKING CHECK!
        if (!empty($validated['table_id'])) {
            if ($this->isTableAlreadyReserved($validated['table_id'], $validated['reservation_date'], $validated['reservation_time'])) {
                $table = Table::find($validated['table_id']);
                $tableName = $table ? $table->table_number : 'Selected Table';

                return response()->json([
                    'success' => false,
                    'message' => "{$tableName} is ALREADY RESERVED on {$validated['reservation_date']} around {$validated['reservation_time']}. Please select another table or time."
                ], 422);
            }
        }

        $reservation = Reservation::create([
            'customer_name'    => $validated['customer_name'],
            'customer_phone'   => $validated['customer_phone'],
            'guest_count'      => $validated['guest_count'],
            'reservation_date' => $validated['reservation_date'],
            'reservation_time' => $validated['reservation_time'],
            'table_id'         => $validated['table_id'] ?? null,
            'status'           => 'confirmed',
            'source'           => 'phone',
            'special_notes'    => $validated['special_notes'] ?? null,
        ]);

        try {
            // 1. When Online or Phone Booking is Created:
            event(new ReservationUpdated('created', $reservation));
        } catch (\Exception $e) {
            Log::warning('WebSocket broadcast for reservation failed: ' . $e->getMessage());
        }

        return response()->json([
            'success'     => true,
            'message'     => "Phone reservation created for {$reservation->customer_name}",
            'reservation' => $reservation->load('table'),
        ], 201);
    }

    /**
     * 4. Staff API: Get Reservations for a Specific Date
     */
    public function getReservationsByDate(Request $request)
    {
        $date = $request->input('date', Carbon::now('Europe/Paris')->toDateString());

        $reservations = Reservation::whereDate('reservation_date', $date)
            ->with(['table', 'client'])
            ->orderBy('reservation_time', 'asc')
            ->get();

        return response()->json($reservations);
    }

    /**
     * 5. Staff API: Update Reservation Status (seated, completed, cancelled, no_show)
     */
    public function updateStatus(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'status'   => 'required|in:confirmed,seated,completed,cancelled,no_show',
            'table_id' => 'nullable|exists:tables,id',
        ]);

        $data = ['status' => $validated['status']];
        if (isset($validated['table_id'])) {
            $data['table_id'] = $validated['table_id'];
        }

        $reservation->update($data);

        try {
            event(new ReservationUpdated('updated', $reservation));
        } catch (\Exception $e) {
            Log::warning('WebSocket broadcast for reservation failed: ' . $e->getMessage());
        }

        return response()->json([
            'success'     => true,
            'message'     => "Reservation status updated to {$reservation->status}",
            'reservation' => $reservation->load('table'),
        ]);
    }


    /**
     * Get reservations belonging to the authenticated customer
     */
    public function getClientReservations(Request $request)
    {
        $clientId = auth('sanctum')->id();
        if (!$clientId) {
            return response()->json([]);
        }

        $reservations = Reservation::where('client_id', $clientId)
            ->with('table')
            ->latest()
            ->get();

        return response()->json($reservations);
    }

    /**
     * Allow customer to cancel their own upcoming reservation
     */
    public function cancelClientReservation(Request $request, Reservation $reservation)
    {
        $clientId = auth('sanctum')->id();
        if ($reservation->client_id !== $clientId) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        if ($reservation->status === 'seated' || $reservation->status === 'completed') {
            return response()->json(['message' => 'Cannot cancel an active or completed booking.'], 422);
        }

        $reservation->update(['status' => 'cancelled']);

        try {
            event(new \App\Events\ReservationUpdated('cancelled', $reservation));
        } catch (\Exception $e) {
        }

        return response()->json([
            'success' => true,
            'message' => 'Reservation cancelled successfully.'
        ]);
    }
}
