<?php
// app/Notifications/TechnicianServiceRequestNotification.php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class ServiceRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $serviceRequest;

    public function __construct($serviceRequest)
    {
        $this->serviceRequest = $serviceRequest;
    }

    public function via($notifiable)
    {
        return ['database', 'fcm']; // Using custom FCM channel
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'New Service Request',
            'body' => "New {$this->serviceRequest->service_name} request available",
            'request_id' => $this->serviceRequest->id,
            'type' => 'service_request',
            'created_at' => now()->toDateTimeString(),
        ];
    }

    public function toFcm($notifiable)
    {
        return [
            'title' => 'New Service Request',
            'body' => "New {$this->serviceRequest->service_name} request available",
            'data' => [
                'type' => 'service_request',
                'request_id' => $this->serviceRequest->id,
                'latitude' => $this->serviceRequest->latitude,
                'longitude' => $this->serviceRequest->longitude,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ],
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'sound' => 'default',
                    'color' => '#FF0000'
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
