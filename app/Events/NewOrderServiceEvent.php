<?php

namespace App\Events;

use App\Models\OrderService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewOrderServiceEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $orderService;
    public $technician;

    /**
     * Create a new event instance.
     */
    public function __construct(OrderService $orderService, $technician)
    {
        $this->orderService = $orderService;
        $this->technician = $technician;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('technician.' . $this->technician->id),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'order_service_id' => $this->orderService->id,
            'title' => 'طلب خدمة جديد',
            'message' => 'لديك طلب خدمة جديد يتوافق مع تخصصك',
            'service_name' => $this->orderService->service->service_name ?? '',
            'category' => $this->orderService->category,
            'date' => $this->orderService->date,
            'time_slot' => $this->orderService->time_slot,
        ];
    }
}
