<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Models\TechnicianOffer;
use App\Models\Technician;
use App\Models\ServiceRequest;
use App\Models\OrderService;

class OfferDeletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $offer;
    protected $technician;
    protected $serviceObject;
    protected $requestType;

    public function __construct(TechnicianOffer $offer, Technician $technician, $serviceObject, $requestType)
    {
        $this->offer = $offer;
        $this->technician = $technician;
        $this->serviceObject = $serviceObject;
        $this->requestType = $requestType;
    }

    public function via($notifiable)
    {
        return ['database', 'fcm'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'تم حذف العرض',
            'body' => "قام {$this->technician->first_name} {$this->technician->last_name} بحذف العرض على طلبك",
            'offer_id' => $this->offer->id,
            'service_request_id' => $this->requestType === 'service_request' ? $this->serviceObject->id : null,
            'order_service_id' => $this->requestType === 'order_service' ? $this->serviceObject->id : null,
            'technician_id' => $this->technician->id,
            'technician_name' => "{$this->technician->first_name} {$this->technician->last_name}",
            'type' => 'offer_deleted',
            'created_at' => now()->toDateTimeString(),
        ];
    }

    public function toFcm($notifiable)
    {
        $data = $this->toDatabase($notifiable);
        
        return [
            'title' => $data['title'],
            'body' => $data['body'],
            'data' => [
                'offer_id' => (string) $data['offer_id'],
                'service_request_id' => $this->requestType === 'service_request' ? (string) $data['service_request_id'] : null,
                'order_service_id' => $this->requestType === 'order_service' ? (string) $data['order_service_id'] : null,
                'type' => 'offer_deleted',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ],
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'sound' => 'default',
                    'color' => '#0066CC'
                ]
            ],
            'apns' => [
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                        'badge' => 1,
                    ]
                ]
            ]
        ];
    }
}