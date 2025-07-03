<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class NewTechnicianOfferNotification extends Notification
{
    protected $title = 'عرض جديد';
    protected $type = 'new_offer';
    protected $body;

    public function __construct(
        public $offerId,
        public $serviceRequestId,
        public $description,
        public $technicianId,
        public $technicianName,
        public $minPrice,
        public $maxPrice
    ) {
        $this->body = "قام {$this->technicianName} بتقديم عرض على طلبك";
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'user_id' => $notifiable->id,
            'title' => 'عرض جديد',
            'body' => 'هناك عرض جديد لطلبك',
            'type' => $this->type,
            'data' => [
                'offer_id' => $this->offerId,
                'service_request_id' => $this->serviceRequestId,
                'description' => $this->description,
                'technician_id' => $this->technicianId,
                'technician_name' => $this->technicianName,
                'min_price' => $this->minPrice,
                'max_price' => $this->maxPrice,
                'currency' => 'جنيه مصري',
                'created_at' => now()->toDateTimeString(),
            ],
            'read_at' => null
        ];
    }
}
