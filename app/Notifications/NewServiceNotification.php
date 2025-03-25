<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use Illuminate\Support\Facades\Storage;

class NewServiceNotification extends Notification
{
    use Queueable;

    protected $orderService;

    public function __construct($orderService)
    {
        $this->orderService = $orderService;
    }

    public function via($notifiable)
    {
        return ['database']; // القناة database لتخزين الإشعار في قاعدة البيانات
    }

    public function toArray($notifiable)
    {
        return [
            'order_id' => $this->orderService->id,
            'service_name' => $this->orderService->serviceType->name,
            'date' => $this->orderService->date,
            'time_slot' => $this->orderService->time_slot,
            'address' => $this->orderService->address,
            'image' => $this->orderService->image ? Storage::url($this->orderService->image) : null,
        ];
    }
}
