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
                'name' => $this->serviceType->name,
            ],
            'description' => $this->description,
            'images' => $this->images, // JSON array of image paths
            'scheduled_date' => $this->scheduled_date->toDateString(),
            'is_urgent' => $this->is_urgent,
            'time_slot' => [
                'id' => $this->timeSlot->id,
                'from' => $this->timeSlot->from,
                'to' => $this->timeSlot->to,
            ],
            'payment_method' => [
                'id' => $this->paymentMethod->id,
                'method' => $this->paymentMethod->name,
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
