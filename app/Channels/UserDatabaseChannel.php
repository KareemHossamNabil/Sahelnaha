<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use App\Models\UserNotification;

class UserDatabaseChannel
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        $data = method_exists($notification, 'toUserDatabase')
            ? $notification->toUserDatabase($notifiable)
            : (method_exists($notification, 'toArray') ? $notification->toArray($notifiable) : []);

        if (empty($data)) {
            return;
        }

        UserNotification::create([
            'user_id' => $notifiable->id,
            'title' => $data['title'] ?? '',
            'body' => $data['body'] ?? '',
            'type' => $data['type'] ?? class_basename($notification),
            'data' => $data,
            'read_at' => null,
        ]);
    }
}
