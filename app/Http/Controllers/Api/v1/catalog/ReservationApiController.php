<?php

namespace App\Http\Controllers\Api\v1\catalog;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\Table;
use App\Events\ReservationUpdated;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ReservationApiController extends Controller
{
    /**
     * Check table availability for date, time, and guest count
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

        // 2-hour reservation conflict window (1 hour before & 1 hour after)
        $bookingTime = Carbon::parse("{$date} {$time}");
        $windowStart = (clone $bookingTime)->subHours(1)->format('H:i:s');
        $windowEnd   = (clone $bookingTime)->addHours(1)->format('H:i:s');

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
     * Public Customer Web Self-Booking (/reservation)
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

        $validated = $request->validate([
            'customer_name'    => ['required', 'string', 'max:255'],
            'customer_phone'   => ['required', 'string', 'max:50'],
            'customer_email'   => ['nullable', 'email', 'max:255'],
            'guest_count'      => ['required', 'integer', 'min:1'],
            'reservation_date' => ['required', 'date', 'after_or_equal:today'],
            'reservation_time' => ['required', 'string'],
            'special_notes'    => ['nullable', 'string', 'max:500'],
        ]);

        $todayInParis = Carbon::now('Europe/Paris')->toDateString();
        $currentTimeInParis = Carbon::now('Europe/Paris')->format('H:i');

        if ($validated['reservation_date'] === $todayInParis && $validated['reservation_time'] < $currentTimeInParis) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot create a reservation for a past time today.',
            ], 422);
        }

        // Auto-assign available table
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
                'message' => "No tables available for {$validated['guest_count']} guests on this date/time. Please choose another slot.",
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
        } catch (\Throwable $e) {
            Log::warning('WebSocket broadcast for reservation failed: ' . $e->getMessage());
        }

        return response()->json([
            'success'     => true,
            'message'     => 'Table reservation confirmed successfully!',
            'reservation' => $reservation->load('table'),
        ], 201);
    }

    /**
     * Staff Cashier Phone Booking
     */
    public function storePhoneBooking(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_name'    => ['required', 'string', 'max:255'],
            'customer_phone'   => ['required', 'string', 'max:50'],
            'guest_count'      => ['required', 'integer', 'min:1'],
            'reservation_date' => ['required', 'date', 'after_or_equal:today'],
            'reservation_time' => ['required', 'string'],
            'table_id'         => ['nullable', 'exists:tables,id'],
            'special_notes'    => ['nullable', 'string', 'max:500'],
        ]);

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
        } catch (\Throwable $e) {}

        return response()->json([
            'success'     => true,
            'message'     => "Phone reservation created for {$reservation->customer_name}.",
            'reservation' => $reservation->load('table'),
        ], 201);
    }

    /**
     * Get Reservations By Date for Hostess / POS
     */
    public function getReservationsByDate(Request $request): JsonResponse
    {
        $date = $request->input('date', Carbon::now('Europe/Paris')->toDateString());

        $reservations = Reservation::whereDate('reservation_date', $date)
            ->with(['table', 'client'])
            ->orderBy('reservation_time', 'asc')
            ->get();

        return response()->json($reservations, 200);
    }

    /**
     * Update Reservation Status (seated, completed, cancelled, no_show)
     */
    public function updateStatus(Request $request, Reservation $reservation): JsonResponse
    {
        $validated = $request->validate([
            'status'   => ['required', 'in:confirmed,seated,completed,cancelled,no_show'],
            'table_id' => ['nullable', 'exists:tables,id'],
        ]);

        $data = ['status' => $validated['status']];
        if (isset($validated['table_id'])) {
            $data['table_id'] = $validated['table_id'];
        }

        $reservation->update($data);

        try {
            event(new ReservationUpdated('updated', $reservation));
        } catch (\Throwable $e) {}

        return response()->json([
            'success'     => true,
            'message'     => "Reservation status updated to {$reservation->status}.",
            'reservation' => $reservation->load('table'),
        ], 200);
    }

    /**
     * Get Authenticated Customer's Reservations
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
     * Customer Cancel Reservation
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
        } catch (\Throwable $e) {}

        return response()->json([
            'success' => true,
            'message' => 'Reservation cancelled successfully.',
        ], 200);
    }
}