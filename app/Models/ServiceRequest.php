<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    protected $fillable = [
        'user_id',
        'service_name',
        'description',
        'images',
        'is_urgent',
        'payment_method'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function schedule()
    {
        return $this->hasOne(ServiceRequestSchedule::class);
    }

    public function address()
    {
        return $this->hasOne(ServiceRequestAddress::class);
    }
}
