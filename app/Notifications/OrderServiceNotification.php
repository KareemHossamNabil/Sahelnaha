<?php

namespace App\Notifications;

use App\Models\OrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class OrderServiceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $orderService;

    /**
     * Create a new notification instance.
     */
    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        // Add 'broadcast' channel for real-time notifications
        return ['database', 'broadcast'];
    }

    /**
     * Get the broadcastable representation of the notification.
     *
     * @param mixed $notifiable
     * @return BroadcastMessage
     */
    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable)
    {
        return [
            'order_service_id' => $this->orderService->id,
            'title' => 'طلب خدمة جديد',
            'message' => 'لديك طلب خدمة جديد يتوافق مع تخصصك',
            'service_name' => $this->orderService->service->service_name ?? '',
            'category' => $this->orderService->category,
            'date' => $this->orderService->date,
            'day' => $this->getArabicDayOfWeek(\Carbon\Carbon::parse($this->orderService->date)->dayOfWeek),
            'time_slot' => $this->orderService->time_slot,
            'address' => $this->orderService->address,
            'long' => $this->orderService->long,
            'lat' => $this->orderService->lat,
            'payment_method' => $this->orderService->payment_method,
            'created_at' => $this->orderService->created_at,
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable)
    {
        return $this->toDatabase($notifiable);
    }

    /**
     * Get Arabic day name from day of week number
     * 
     * @param int $dayOfWeek
     * @return string
     */
    private function getArabicDayOfWeek(int $dayOfWeek): string
    {
        $days = [
            0 => 'الأحد',
            1 => 'الإثنين',
            2 => 'الثلاثاء',
            3 => 'الأربعاء',
            4 => 'الخميس',
            5 => 'الجمعة',
            6 => 'السبت',
        ];

        return $days[$dayOfWeek] ?? '';
    }
}
