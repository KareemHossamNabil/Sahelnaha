<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = ['key', 'name'];

    // العلاقة مع الطلبات
    public function orderServices()
    {
        return $this->hasMany(OrderService::class, 'payment_method_id');
    }
}
