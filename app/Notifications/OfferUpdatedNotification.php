<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Models\TechnicianOffer;
use App\Models\Technician;
use App\Models\ServiceRequest;
use App\Models\OrderService;

class OfferUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $offer;
    protected $oldOffer;
    protected $technician;
    protected $serviceObject;
    protected $requestType;

    public function __construct(TechnicianOffer $offer, TechnicianOffer $oldOffer, Technician $technician, $serviceObject, $requestType)
    {
        $this->offer = $offer;
        $this->oldOffer = $oldOffer;
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
            'title' => 'تم تحديث العرض',
            'body' => "قام {$this->technician->first_name} {$this->technician->last_name} بتحديث العرض على طلبك",
            'offer_id' => $this->offer->id,
            'service_request_id' => $this->requestType === 'service_request' ? $this->serviceObject->id : null,
            'order_service_id' => $this->requestType === 'order_service' ? $this->serviceObject->id : null,
            'technician_id' => $this->technician->id,
            'technician_name' => "{$this->technician->first_name} {$this->technician->last_name}",
            'min_price' => $this->offer->min_price,
            'max_price' => $this->offer->max_price,
            'currency' => $this->offer->currency,
            'type' => 'offer_updated',
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
                'type' => 'offer_updated',
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