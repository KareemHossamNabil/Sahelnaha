<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class OrderServiceNotification extends Notification
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toFcm($notifiable)
    {
        // return FcmMessage::create()
        //     ->setNotification(FcmNotification::create()
        //         ->setTitle('طلب خدمة جديد')
        //         ->setBody('يوجد طلب جديد لتخصصك')
        //         ->setImage(null))
        //     ->setData([
        //         'order_id' => $this->data['id'],
        //         'category' => $this->data['category'],
        //     ]);
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => 'طلب خدمة جديد',
            'body' => 'يوجد طلب جديد لتخصصك',
            'data' => $this->data
        ];
    }
}
