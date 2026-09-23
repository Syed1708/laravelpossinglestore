<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KdsOrderUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $message;
    public ?Order $order;
    public ?string $status;

    public function __construct(string $message = 'update', ?Order $order = null)
    {
        $this->message = $message;
        $this->order   = $order;
        $this->status  = $order ? ($order->preparation_status ?? $order->status) : null;
    }

    /**
     * Channels to broadcast on
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new Channel('kds-channel'), // Broadcast to Chef, Packer, and Cashier screens
        ];

        // Broadcast to individual customer live tracker on Next.js
        if ($this->order) {
            $channels[] = new Channel('orders.' . $this->order->id);
        }

        return $channels;
    }

    /**
     * Broadcast event name (Listening as 'order-event' in Pusher / '.order-event' in Echo)
     */
    public function broadcastAs(): string
    {
        return 'order-event';
    }

    /**
     * 🚀 CRITICAL FIX: Send a clean, lightweight payload (<300 bytes)
     * Prevents exceeding Reverb's 10KB max_message_size limit.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'message'            => $this->message,
            'status'             => $this->status,
            'order_id'           => $this->order?->id,
            'sequence_number'    => $this->order?->sequence_number,
            'preparation_status' => $this->order?->preparation_status,
            'order_type'         => $this->order?->order_type,
            'customer_name'      => $this->order?->customer_name,
        ];
    }
}