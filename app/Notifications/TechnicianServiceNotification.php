<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TechnicianServiceNotification extends Notification
{
    use Queueable;

    protected $serviceRequest;

    /**
     * Create a new notification instance.
     */
    public function __construct(ServiceRequest $serviceRequest)
    {
        $this->serviceRequest = $serviceRequest;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        // يمكنك إضافة قنوات أخرى مثل: broadcast، vonage، slack
        return ['database'];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable)
    {
        // استخراج مصفوفة الصور من الحقل المخزن بصيغة JSON
        $images = json_decode($this->serviceRequest->images, true) ?? [];

        return [
            'service_request_id' => $this->serviceRequest->id,
            'title' => 'طلب خدمة جديد',
            'message' => 'لديك طلب خدمة جديد في انتظار موافقتك',
            'service_type' => $this->serviceRequest->serviceType->name ?? '',
            'scheduled_date' => $this->serviceRequest->scheduled_date,
            'time_slot' => $this->serviceRequest->timeSlot->name ?? '',
            'address' => $this->serviceRequest->address,
            'is_urgent' => $this->serviceRequest->is_urgent,
            'description' => $this->serviceRequest->description,
            'images' => $images, // إضافة الصور للإشعار
        ];
    }
    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable)
    {
        // استخراج مصفوفة الصور من الحقل المخزن بصيغة JSON
        $images = json_decode($this->serviceRequest->images, true) ?? [];

        return [
            'service_request_id' => $this->serviceRequest->id,
            'title' => 'طلب خدمة جديد',
            'message' => 'لديك طلب خدمة جديد في انتظار موافقتك',
            'service_type' => $this->serviceRequest->serviceType->name ?? '',
            'scheduled_date' => $this->serviceRequest->scheduled_date,
            'time_slot' => $this->serviceRequest->timeSlot->name ?? '',
            'address' => $this->serviceRequest->address,
            'is_urgent' => $this->serviceRequest->is_urgent,
            'description' => $this->serviceRequest->description,
            'images' => $images, // إضافة الصور للإشعار
        ];
    }
}
