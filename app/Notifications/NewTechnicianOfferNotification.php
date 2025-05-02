<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Models\TechnicianOffer;
use App\Models\Technician;
use App\Models\ServiceRequest;

class NewTechnicianOfferNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $offer;
    protected $technician;
    protected $serviceRequest;

    /**
     * Create a new notification instance.
     */
    public function __construct(TechnicianOffer $offer, Technician $technician, ServiceRequest $serviceRequest)
    {
        $this->offer = $offer;
        $this->technician = $technician;
        $this->serviceRequest = $serviceRequest;
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
            'title' => 'عرض جديد على طلبك',
            'body' => "قام {$this->technician->first_name} {$this->technician->last_name} بتقديم عرض على طلبك",
            'offer_id' => $this->offer->id,
            'service_request_id' => $this->serviceRequest->id,
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
                'service_request_id' => (string) $data['service_request_id'],
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