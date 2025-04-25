<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewServiceRequestNotification extends Notification
{
    use Queueable;

    protected $serviceRequest;

    public function __construct(ServiceRequest $serviceRequest)
    {
        $this->serviceRequest = $serviceRequest;
    }

    public function via($notifiable)
    {
        return ['database']; // أو يمكن استخدام أي قناة أخرى
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => 'هناك طلب خدمة جديد يتعلق بتخصصك.',
            'service_request_id' => $this->serviceRequest->id,
            'service_name' => $this->serviceRequest->service_name,
            'date' => optional($this->serviceRequest->schedule)->date,
            'time' => optional($this->serviceRequest->schedule)->time,
            'is_urgent' => $this->serviceRequest->getAttribute('is_urgent') ?? false,
            'address' => optional($this->serviceRequest->address)->full_address,
        ];
    }
}
