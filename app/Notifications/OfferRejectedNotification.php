<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\TechnicianOffer;

class OfferRejectedNotification extends Notification implements ShouldQueue
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
            ->subject('تم رفض عرضك')
            ->line('تم رفض عرضك للخدمة.')
            ->line('تفاصيل العرض:')
            ->line('الوصف: ' . $this->offer->description)
            ->line('السعر: ' . $this->offer->min_price . ' - ' . $this->offer->max_price . ' ' . $this->offer->currency)
            ->action('عرض التفاصيل', url('/offers/' . $this->offer->id))
            ->line('نتمنى لك التوفيق في العروض القادمة!');
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
            'message' => 'تم رفض عرضك للخدمة',
            'type' => 'offer_rejected'
        ];
    }
}
