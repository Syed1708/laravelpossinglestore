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

        // Eager-load items, category relations, and customer info in WebSocket payload
        $this->order = $order ? $order->loadMissing(['items.product.category', 'client']) : null;
        $this->status = $order ? ($order->preparation_status ?? $order->status) : null;
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
     * Broadcast event name (Listening as '.order-event' on Next.js Laravel Echo)
     */
    public function broadcastAs(): string
    {
        return 'order-event';
    }
}