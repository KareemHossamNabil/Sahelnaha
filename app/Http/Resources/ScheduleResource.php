<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
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
            'date' => $this->date,
            'timeSlotId' => $this->time_slot_id,
            'isUrgent' => (bool) $this->is_urgent,
            'confirmed' => $this->status === 'confirmed',
            'createdAt' => $this->created_at->toIso8601String(),
        ];
    }
}
