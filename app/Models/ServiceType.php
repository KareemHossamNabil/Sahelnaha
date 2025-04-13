<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceType extends Model
{
    protected $fillable = ['id', 'name'];

    // العلاقة مع الطلبات
    public function orderServices()
    {
        return $this->hasMany(OrderService::class, 'service_type_id');
    }
}
