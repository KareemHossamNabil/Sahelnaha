<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MotivationEvent extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description', 'period', 'start_date', 'end_date'];
}
