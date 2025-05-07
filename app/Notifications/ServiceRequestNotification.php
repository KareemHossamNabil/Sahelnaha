<?php

namespace App\Notifications;

// app/Notifications/TechnicianServiceRequestNotification.php
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class ServiceRequestNotification extends Notification
{
    protected $serviceRequest;

    public function __construct($serviceRequest)
    {
        $this->serviceRequest = $serviceRequest;
    }

    public function via($notifiable)
    {
        return ['database']; // <-- استخدم قاعدة البيانات فقط
    }

    public function toDatabase($notifiable)
    {
        return [
            'service_request_id' => $this->serviceRequest->id,
            'service_name' => $this->serviceRequest->service_name,
            'user_id' => $this->serviceRequest->user_id,
            'timestamp' => now(),
        ];
    }
}
