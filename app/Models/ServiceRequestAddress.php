<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRequestAddress extends Model
{
    protected $fillable = ['service_request_id', 'payment_method', 'address'];

    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }
}
