<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechnicianNotification extends Model
{
    protected $fillable = [
        'technician_id',
        'type',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }
}
