<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['service_name', 'description'];

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_service');
    }
}
