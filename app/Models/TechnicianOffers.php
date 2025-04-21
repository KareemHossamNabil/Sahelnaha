<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TechnicianOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_request_id',
        'technician_id',
        'description',
        'min_price',
        'max_price',
        'currency',
        'status'
    ];

    // العلاقة مع طلب الخدمة
    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    // العلاقة مع الفني
    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }
}
