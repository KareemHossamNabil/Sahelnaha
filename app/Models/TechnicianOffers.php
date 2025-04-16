<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TechnicianOffers extends Model
{
    use HasFactory;

    protected $fillable = [
        'issue_id',
        'technician_id',
        'description',
        'min_price',
        'max_price',
        'currency', // EGP
        'status',  // pending , completed , rejected
    ];

    public function issue()
    {
        return $this->belongsTo(UserIssues::class, 'issue_id');
    }

    public function technician()
    {
        return $this->belongsTo(Technician::class, 'technician_id');
    }
}
