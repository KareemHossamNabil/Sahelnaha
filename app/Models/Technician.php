<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Technician extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'technicians';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'address',
        'phone',
        'register_otp',
        'reset_otp',
        'is_verified',
        'identity_image',
        'is_verified_identity',
        'experience_text',
        'work_images',
        'qr_code',
        'social_id',
        'social_type',
    ];

    protected $hidden = [
        'password',
        'register_otp',
        'reset_otp',
        'social_id',
    ];
}
