<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KdsOrderUpdated implements ShouldBroadcastNow 
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $order;
    public $status;

    public function __construct($message = 'update', $order = null)
    {
        $this->message = $message;
        $this->order = $order;
        $this->status = $order ? ($order->preparation_status ?? $order->status) : null;
    }

    public function broadcastOn()
    {
        $channels = [new Channel('kds-channel')];

        if ($this->order) {
            $channels[] = new Channel('orders.' . $this->order->id);
        }

        return $channels;
    }

    public function broadcastAs()
    {
        return 'order-event';
    }
}