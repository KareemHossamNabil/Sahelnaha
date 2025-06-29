<?php

namespace App\Events;

use App\Models\TechnicianOffer;
use App\Models\Technician;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TechnicianOfferEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $action;
    public $offer;
    public $technician;
    public $serviceObject;
    public $requestType;

    /**
     * Create a new event instance.
     */
    public function __construct($action, $offer, $technician, $serviceObject, $requestType)
    {
        $this->action = $action;
        $this->offer = $offer;
        $this->technician = $technician;
        $this->serviceObject = $serviceObject;
        $this->requestType = $requestType;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->serviceObject->user_id),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'offer_id' => $this->offer->id,
            'technician' => [
                'id' => $this->technician->id,
                'name' => $this->technician->first_name . ' ' . $this->technician->last_name
            ],
            'request_type' => $this->requestType,
            'request_id' => $this->requestType === 'service_request' 
                ? $this->offer->service_request_id 
                : $this->offer->order_service_id
        ];
    }
}