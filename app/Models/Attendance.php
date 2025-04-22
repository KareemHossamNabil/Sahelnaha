<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'technician_id',
        'scanned_at',
    ];

    public function technician()
    {
        return $this->belongsTo(Technician::class);
    }
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
