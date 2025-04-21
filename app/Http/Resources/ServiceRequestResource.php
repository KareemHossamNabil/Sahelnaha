<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->first_name . ' ' . $this->user->last_name,
                'email' => $this->user->email,
            ],
            'service_type' => [
                'id' => $this->serviceType->id,
                'name' => $this->serviceType->name_ar,
            ],
            'description' => $this->description,
            'images' => $this->images, // JSON array of image paths
            'scheduled_date' => $this->scheduled_date->toDateString(),
            'is_urgent' => $this->is_urgent,
            'time_slot' => [
                'id' => $this->timeSlot->id,
                'name' => $this->timeSlot->name,
                'start_at' => $this->timeSlot->start_time,
                'end_at' => $this->timeslot->end_time
            ],
            'payment_method' => [
                'id' => $this->paymentMethod->id,
                'type' => $this->paymentMethod->type,
                'card_number' => $this->paymentMethod->card_number,

            ],
            'address' => $this->address,
            'status' => $this->status,
            'technician' => $this->technician ? [
                'id' => $this->technician->id,
                'name' => $this->technician->name,
            ] : null,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
