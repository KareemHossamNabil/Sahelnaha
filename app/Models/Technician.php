<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Technician extends Model
{
    use Notifiable;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'rating',
        'occupation',
        'years_of_experience',
    ];
}
