<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRequestSchedule extends Model
{
    protected $fillable = ['service_request_id', 'day', 'date', 'time_range'];

    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }
}
