<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\TechnicianOffer;

class ServiceCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $offer;

    /**
     * Create a new notification instance.
     *
     * @param TechnicianOffer $offer
     * @return void
     */
    public function __construct(TechnicianOffer $offer)
    {
        $this->offer = $offer;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('تم إكمال الخدمة')
            ->line('تم إكمال الخدمة وتقييمها من قبل العميل.')
            ->line('التقييم: ' . $this->offer->rating . '/5')
            ->when($this->offer->comment, function ($message) {
                return $message->line('التعليق: ' . $this->offer->comment);
            })
            ->action('عرض التفاصيل', url('/offers/' . $this->offer->id))
            ->line('شكراً لخدمتك المميزة!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'offer_id' => $this->offer->id,
            'message' => 'تم إكمال الخدمة وتقييمها',
            'rating' => $this->offer->rating,
            'type' => 'service_completed'
        ];
    }
}
