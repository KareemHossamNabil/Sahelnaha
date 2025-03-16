<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'task_type',
        'description',
        'image',
        'is_urgent',
        'time_slot',
        'address',
        'payment_method',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
