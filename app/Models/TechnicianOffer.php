<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TechnicianOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'technician_id',
        'service_request_id',
        'order_service_id',
        'description',
        'min_price',
        'max_price',
        'currency',
        'status',
        'request_type',
        'final_price',
        'invoice_image',
        'rating',
        'review',
        'cancellation_reason',
    ];

    protected $casts = [
        'status' => 'string',
        'min_price' => 'decimal:2',
        'max_price' => 'decimal:2',
        'final_price' => 'decimal:2',
        'rating' => 'integer',
    ];

    /**
     * Get the technician that made the offer.
     */
    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }

    /**
     * Get the service request that the offer is for.
     */
    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    /**
     * Get the order service that the offer is for.
     */
    public function orderService()
    {
        return $this->belongsTo(OrderService::class);
    }
}
