<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Models\TechnicianOffer;
use App\Models\User;

class OfferAcceptedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $offer;
    protected $user;
    protected $serviceObject;
    protected $requestType;

    /**
     * Create a new notification instance.
     */
    public function __construct(TechnicianOffer $offer, User $user, $serviceObject, $requestType)
    {
        $this->offer = $offer;
        $this->user = $user;
        $this->serviceObject = $serviceObject;
        $this->requestType = $requestType;
        
        // Ensure notification is sent after database transaction is committed
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return ['database', 'fcm'];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable)
    {
        return [
            'title' => 'تم قبول العرض',
            'body' => "قام {$this->user->name} بقبول عرضك",
            'offer_id' => $this->offer->id,
            'service_request_id' => $this->requestType === 'service_request' ? $this->serviceObject->id : null,
            'order_service_id' => $this->requestType === 'order_service' ? $this->serviceObject->id : null,
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'min_price' => $this->offer->min_price,
            'max_price' => $this->offer->max_price,
            'currency' => $this->offer->currency,
            'type' => 'offer_accepted',
            'created_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable)
    {
        return $this->toDatabase($notifiable);
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
                'service_request_id' => $this->requestType === 'service_request' ? (string) $data['service_request_id'] : null,
                'order_service_id' => $this->requestType === 'order_service' ? (string) $data['order_service_id'] : null,
                'type' => 'offer_accepted',
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