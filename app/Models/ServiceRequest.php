<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'service_type_id',
        'description',
        'images',
        'scheduled_date',
        'is_urgent',
        'time_slot_id',
        'payment_method_id',
        'address',
        'status',
        'technician_id',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'images' => 'array', // لأننا بنخزنها كـ JSON
        'is_urgent' => 'boolean',
    ];

    // العلاقات
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function timeSlot()
    {
        return $this->belongsTo(TimeSlot::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}
