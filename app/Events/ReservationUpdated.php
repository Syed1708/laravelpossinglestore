<?php

namespace App\Events;

use App\Models\Reservation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReservationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $action; // 'created', 'seated', 'completed', 'cancelled'
    public $reservation;

    public function __construct(string $action, Reservation $reservation)
    {
        $this->action = $action;
        // Load table & client relationships in WebSocket payload
        $this->reservation = $reservation->loadMissing(['table', 'client']);
    }

    public function broadcastOn()
    {
        $channels = [
            new Channel('kds-channel'), // Broadcasts to Admin & Hostess Floor Plan
        ];

        // Broadcasts to specific customer live tracker
        if ($this->reservation) {
            $channels[] = new Channel('reservations.' . $this->reservation->id);
        }

        return $channels;
    }

    public function broadcastAs()
    {
        return 'reservation-event';
    }
}