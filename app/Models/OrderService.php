<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderService extends Model
{
    protected $fillable = [
        'service_type_id',
        'description',
        'image',
        'date',
        'time_slot',
        'is_urgent',
        'address',
        'payment_method_id',
        'status'
    ];

    // العلاقة مع نوع الخدمة
    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class, 'service_type_id');
    }

    // العلاقة مع طريقة الدفع
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }
}
