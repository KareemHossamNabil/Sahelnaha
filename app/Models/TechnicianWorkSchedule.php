<?php
// app/Models/TechnicianWorkSchedule.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicianWorkSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'technician_id',
        'service_request_id',
        'order_service_id',
        'start_time',
        'end_time',
        'status',
        'notes'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Get the technician that owns the work schedule.
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    /**
     * Get the service request associated with the work schedule.
     */
    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    /**
     * Get the order service associated with the work schedule.
     */
    public function orderService(): BelongsTo
    {
        return $this->belongsTo(OrderService::class);
    }

    /**
     * Get the request type (service_request or order_service).
     */
    public function getRequestTypeAttribute(): string
    {
        return $this->service_request_id ? 'service_request' : 'order_service';
    }

    /**
     * Get the request object (either service request or order service).
     */
    public function getRequestAttribute()
    {
        return $this->service_request_id ? $this->serviceRequest : $this->orderService;
    }
}
