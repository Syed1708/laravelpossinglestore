<?php

namespace App\Http\Controllers\Api\v1\catalog;

use App\Events\ReservationUpdated;
use App\Helpers\StoreHoursHelper;
use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Table;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReservationApiController extends Controller
{
    /**
     * 1. Public API: Check table availability for date, time, and guest count
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date'   => ['required', 'date'],
            'time'   => ['required', 'string'],
            'guests' => ['required', 'integer', 'min:1', 'max:30'],
            'zone'   => ['nullable', 'string', 'in:indoor,terrace,vip'],
        ]);

        $date   = $validated['date'];
        $time   = $validated['time'];
        $guests = (int) $validated['guests'];
        $zone   = $validated['zone'] ?? null;

        $query = Table::where('is_active', true)->where('capacity', '>=', $guests);

        if ($zone) {
            $query->where('zone', $zone);
        }

        // 90-minute conflict window before and after requested booking time
        $bookingTime = Carbon::parse("{$date} {$time}");
        $windowStart = (clone $bookingTime)->subMinutes(90)->format('H:i:s');
        $windowEnd   = (clone $bookingTime)->addMinutes(90)->format('H:i:s');

        $availableTables = $query->whereDoesntHave('reservations', function ($q) use ($date, $windowStart, $windowEnd) {
            $q->where('reservation_date', $date)
                ->whereIn('status', ['confirmed', 'seated'])
                ->whereBetween('reservation_time', [$windowStart, $windowEnd]);
        })->get();

        return response()->json([
            'is_available'     => $availableTables->isNotEmpty(),
            'available_tables' => $availableTables,
        ], 200);
    }

    /**
     * 2. Customer Web Self-Booking (/reservation)
     */
    public function storeOnline(Request $request): JsonResponse
    {
        $clientId = auth('sanctum')->id() ?? $request->user('sanctum')?->id;

        if (!$clientId) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required. Please sign in to reserve a table.',
            ], 401);
        }

        // 🛑 Check 1: Global Settings Toggle
        if (!StoreHoursHelper::canAcceptReservations()) {
            return response()->json([
                'success' => false,
                'message' => 'Table reservations are currently disabled.',
            ], 422);
        }

        $validated = $request->validate([
            'customer_name'    => ['required', 'string', 'max:255'],
            'customer_phone'   => ['required', 'string', 'max:50'],
            'customer_email'   => ['nullable', 'email', 'max:255'],
            'guest_count'      => ['required', 'integer', 'min:1'],
            'reservation_date' => ['required', 'date', 'after_or_equal:today'],
            'reservation_time' => ['required', 'string'],
            'special_notes'    => ['nullable', 'string', 'max:500'],
        ]);

        $today = StoreHoursHelper::now()->toDateString();
$currentTime = StoreHoursHelper::now()->format('H:i');

        // 🛑 Check 2: Block Past Dates
        if ($validated['reservation_date'] < $today) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot create a reservation for a past date.',
            ], 422);
        }

        // 🛑 Check 3: Block Past Time (If booking for today)
        if ($validated['reservation_date'] === $today && $validated['reservation_time'] < $currentTime) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot create a reservation for a past time today.',
            ], 422);
        }

        // 🛑 Check 4: Block Out-of-Schedule Times
        if (!StoreHoursHelper::isTimeInSchedule($validated['reservation_time'])) {
            return response()->json([
                'success' => false,
                'message' => 'Booking time (' . $validated['reservation_time'] . ') is outside opening hours (' . StoreHoursHelper::getScheduleText() . ').',
            ], 422);
        }

        // 🚀 Auto-assign table that fits party size
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
            ->orderBy('capacity', 'asc') // Pick smallest suitable table first
            ->first();

        if (!$availableTable) {
            return response()->json([
                'success' => false,
                'message' => "No tables available for {$validated['guest_count']} guests on {$validated['reservation_date']} at {$validated['reservation_time']}. Please select another time slot.",
            ], 422);
        }

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

        try {
            event(new ReservationUpdated('created', $reservation));
        } catch (Throwable $e) {
            Log::warning('WebSocket broadcast for reservation failed: ' . $e->getMessage());
        }

        return response()->json([
            'success'     => true,
            'message'     => 'Table reservation confirmed successfully!',
            'reservation' => $reservation->load('table'),
        ], 201);
    }

    /**
     * 3. Staff Cashier Phone Booking (/pos or /admin)
     */
    public function storePhoneBooking(Request $request): JsonResponse
    {
        // 🛑 Check 1: Global Setting Toggle
        if (!StoreHoursHelper::canAcceptReservations()) {
            return response()->json([
                'success' => false,
                'message' => StoreHoursHelper::getClosedMessage(),
            ], 422);
        }

        $validated = $request->validate([
            'customer_name'    => ['required', 'string', 'max:255'],
            'customer_phone'   => ['required', 'string', 'max:50'],
            'guest_count'      => ['required', 'integer', 'min:1'],
            'reservation_date' => ['required', 'date', 'after_or_equal:today'],
            'reservation_time' => ['required', 'string'],
            'table_id'         => ['nullable', 'exists:tables,id'],
            'special_notes'    => ['nullable', 'string', 'max:500'],
        ]);

        $today = StoreHoursHelper::now()->toDateString();
        $currentTime = StoreHoursHelper::now()->format('H:i');

        if ($validated['reservation_date'] === $today && $validated['reservation_time'] < $currentTime) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot create a reservation for a past time today.',
            ], 422);
        }

        if (!StoreHoursHelper::isTimeInSchedule($validated['reservation_time'])) {
            return response()->json([
                'success' => false,
                'message' => 'Time (' . $validated['reservation_time'] . ') is outside schedule (' . StoreHoursHelper::getScheduleText() . ').',
            ], 422);
        }

        // 🛑 Check 2: Table Double-Booking Prevention
        if (!empty($validated['table_id'])) {
            if ($this->isTableAlreadyReserved($validated['table_id'], $validated['reservation_date'], $validated['reservation_time'])) {
                $table = Table::find($validated['table_id']);
                $tableName = $table ? $table->table_number : 'Selected Table';

                return response()->json([
                    'success' => false,
                    'message' => "{$tableName} is ALREADY RESERVED on {$validated['reservation_date']} around {$validated['reservation_time']}. Please select another table or time.",
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
            event(new ReservationUpdated('created', $reservation));
        } catch (Throwable $e) {
            Log::warning('WebSocket broadcast for reservation failed: ' . $e->getMessage());
        }

        return response()->json([
            'success'     => true,
            'message'     => "Phone reservation created for {$reservation->customer_name}.",
            'reservation' => $reservation->load('table'),
        ], 201);
    }

    /**
     * 4. Get Reservations By Date for Hostess / POS
     */
    public function getReservationsByDate(Request $request): JsonResponse
    {

        $date = $request->input('date', StoreHoursHelper::now()->toDateString());


        $reservations = Reservation::whereDate('reservation_date', $date)
            ->with(['table', 'client'])
            ->orderBy('reservation_time', 'asc')
            ->get();

        return response()->json($reservations, 200);
    }

    /**
     * 5. Update Reservation Status
     */
    public function updateStatus(Request $request, Reservation $reservation): JsonResponse
    {
        $validated = $request->validate([
            'status'   => ['required', 'in:confirmed,seated,completed,cancelled,no_show'],
            'table_id' => ['nullable', 'exists:tables,id'],
        ]);

        $data = ['status' => $validated['status']];
        if (array_key_exists('table_id', $validated)) {
            $data['table_id'] = $validated['table_id'];
        }

        $reservation->update($data);

        try {
            event(new ReservationUpdated('updated', $reservation));
        } catch (Throwable $e) {}

        return response()->json([
            'success'     => true,
            'message'     => "Reservation status updated to {$reservation->status}.",
            'reservation' => $reservation->load('table'),
        ], 200);
    }

    /**
     * 6. Authenticated Customer Reservations
     */
    public function getClientReservations(Request $request): JsonResponse
    {
        $clientId = auth('sanctum')->id();
        if (!$clientId) {
            return response()->json([], 200);
        }

        $reservations = Reservation::where('client_id', $clientId)
            ->with('table')
            ->latest()
            ->get();

        return response()->json($reservations, 200);
    }

    /**
     * 7. Customer Cancel Reservation
     */
    public function cancelClientReservation(Request $request, Reservation $reservation): JsonResponse
    {
        $clientId = auth('sanctum')->id();
        if ($reservation->client_id !== $clientId) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        if (in_array($reservation->status, ['seated', 'completed'])) {
            return response()->json(['message' => 'Cannot cancel an active or completed booking.'], 422);
        }

        $reservation->update(['status' => 'cancelled']);

        try {
            event(new ReservationUpdated('cancelled', $reservation));
        } catch (Throwable $e) {}

        return response()->json([
            'success' => true,
            'message' => 'Reservation cancelled successfully.',
        ], 200);
    }

    /**
     * 🚀 Helper: Checks if a table is already booked within a 90-minute window
     */
    private function isTableAlreadyReserved($tableId, $date, $time, $excludeReservationId = null): bool
    {
        if (!$tableId) {
            return false;
        }

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
}