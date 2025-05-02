<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Models\TechnicianOffer;
use App\Models\Technician;

class NewTechnicianOfferNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $offer;
    protected $technician;
    protected $serviceObject;
    protected $requestType;

    /**
     * Create a new notification instance.
     */
    public function __construct(TechnicianOffer $offer, Technician $technician, $serviceObject, $requestType)
    {
        $this->offer = $offer;
        $this->technician = $technician;
        $this->serviceObject = $serviceObject;
        $this->requestType = $requestType;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return ['database', 'fcm', 'user-database'];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable)
    {
        return [
            'title' => 'عرض جديد على طلبك',
            'body' => "قام {$this->technician->first_name} {$this->technician->last_name} بتقديم عرض على طلبك",
            'offer_id' => $this->offer->id,
            'service_request_id' => $this->requestType === 'service_request' ? $this->serviceObject->id : null,
            'order_service_id' => $this->requestType === 'order_service' ? $this->serviceObject->id : null,
            'technician_id' => $this->technician->id,
            'technician_name' => "{$this->technician->first_name} {$this->technician->last_name}",
            'min_price' => $this->offer->min_price,
            'max_price' => $this->offer->max_price,
            'currency' => $this->offer->currency,
            'type' => 'new_offer',
            'created_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Get the custom database representation of the notification.
     */
    public function toUserDatabase($notifiable)
    {
        return [
            'title' => 'عرض جديد على طلبك',
            'body' => "قام {$this->technician->first_name} {$this->technician->last_name} بتقديم عرض على طلبك",
            'offer_id' => $this->offer->id,
            'service_request_id' => $this->requestType === 'service_request' ? $this->serviceObject->id : null,
            'order_service_id' => $this->requestType === 'order_service' ? $this->serviceObject->id : null,
            'technician_id' => $this->technician->id,
            'technician_name' => "{$this->technician->first_name} {$this->technician->last_name}",
            'min_price' => $this->offer->min_price,
            'max_price' => $this->offer->max_price,
            'currency' => $this->offer->currency,
            'type' => 'new_offer',
            'created_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Get the FCM representation of the notification.
     */
    public function toFcm($notifiable)
    {
        $data = $this->toDatabase($notifiable);

        return [
            'title' => $data['title'],
            'body' => $data['body'],
            'data' => [
                'offer_id' => (string) $data['offer_id'],
                'service_request_id' => $this->requestType === 'service_request' ? (string) $this->serviceObject->id : null,
                'order_service_id' => $this->requestType === 'order_service' ? (string) $this->serviceObject->id : null,
                'type' => 'new_offer',
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
