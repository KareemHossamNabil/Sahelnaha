<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class NewTechnicianOfferNotification extends Notification
{
    public function __construct(
        public $offerId,
        public $serviceRequestId,
        public $description,
        public $technicianId,
        public $technicianName,
        public $minPrice,
        public $maxPrice,
    ) {}

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'عرض جديد',
            'body' => sprintf('قام %s بتقديم عرض على طلبك', $this->technicianName),
            'type' => 'new_offer',
            'data' => [
                'offer_id' => $this->offerId,
                'service_request_id' => $this->serviceRequestId,
                'order_service_id' => null,
                'description' => $this->description,
                'technician_id' => $this->technicianId,
                'technician_name' => $this->technicianName,
                'min_price' => $this->minPrice,
                'max_price' => $this->maxPrice,
                'currency' => 'جنيه مصري',
                'created_at' => now()->toDateTimeString(),
            ]
        ];
    }
}