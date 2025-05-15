<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ServiceRequestNotification extends Notification
{
    protected $serviceRequest;

    public function __construct($serviceRequest)
    {
        $this->serviceRequest = $serviceRequest;
    }

    public function via($notifiable)
    {
        return ['database'];  // تخزين الإشعار في قاعدة البيانات
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'طلب خدمة جديد',
            'body' => 'تم نشر طلب خدمة جديد بالقرب منك. تحقق من التفاصيل الآن.',
            'service_request_id' => $this->serviceRequest->id,
            'service_name' => $this->serviceRequest->service_name,
            'user_id' => $this->serviceRequest->user_id,
            'address' => $this->serviceRequest->address,
            'longitude' => $this->serviceRequest->longitude,
            'latitude' => $this->serviceRequest->latitude,
            'date' => $this->serviceRequest->date,
            'time_slot' => $this->serviceRequest->time_slot,
            'is_urgent' => $this->serviceRequest->is_urgent,
            'type' => 'new_service_request', // نوع الإشعار
            'created_at' => now()->toDateTimeString(), // التاريخ والوقت الحالي
        ];
    }
}
