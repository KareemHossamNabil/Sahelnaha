<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'address',
        'social_id',
        'social_type',
        'phone',
        'register_otp',
        'reset_otp',
        'profile_picture',
        'is_verified'
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // protected function setPasswordAttribute($value)
    // {
    //     $this->attributes['password'] = Hash::make($value);
    // }

    /**
     * Get the user's custom notifications.
     */
    public function userNotifications()
    {
        return $this->hasMany(UserNotification::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the user's unread custom notifications.
     */
    public function unreadUserNotifications()
    {
        return $this->userNotifications()->whereNull('read_at');
    }

    /**
     * Get the service requests owned by the user.
     */
    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class);
    }

    /**
     * Get the order services owned by the user.
     */
    public function orderServices()
    {
        return $this->hasMany(OrderService::class);
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

    public function notifications()
    {
        return $this->hasMany(UserNotification::class);
    }
    public function scannedAttendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
