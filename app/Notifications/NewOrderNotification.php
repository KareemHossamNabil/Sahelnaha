<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewOrderNotification extends Notification
{
    use Queueable;

    protected $order;

    public function __construct($order)
    {
        $this->order = $order;
    }

    public function via($notifiable)
    {
        return ['database']; // يمكنك استبدالها بـ Firebase أو البريد الإلكتروني
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "طلب جديد: " . $this->order->task_type,
            'order_id' => $this->order->id
        ];
    }
}
