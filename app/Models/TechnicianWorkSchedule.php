<?php
// app/Models/TechnicianWorkSchedule.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TechnicianWorkSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'technician_id',
        'service_request_id',
        'title',
        'location',
        'start_time',
        'price',
    ];

    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }
}
