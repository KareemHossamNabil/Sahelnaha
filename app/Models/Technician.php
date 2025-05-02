<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Technician extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

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
    ];

    protected $hidden = [
        'password',
        'register_otp',
        'reset_otp',
    ];

    public function technicianNotifications()
    {
        return $this->hasMany(TechnicianNotification::class);
    }

    /**
     * Get the offers made by the technician.
     */
    public function offers()
    {
        return $this->hasMany(TechnicianOffer::class);
    }

    /**
     * Route notifications for the FCM channel.
     *
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return string
     */
    public function routeNotificationForFcm($notification)
    {
        return $this->device_token;
    }
}
