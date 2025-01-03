<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens; // إضافة الـ HasApiTokens
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use SoftDeletes, HasApiTokens, HasFactory, Notifiable; // إضافة الـ HasApiTokens

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'address',
        'social_id',
        'social_type',
        'phone'
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // دالة لإنشاء علاقة بين المستخدم والطلبات
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // تعيين كلمة السر وتشفيرها
    // protected function setPasswordAttribute($value)
    // {
    //     $this->attributes['password'] = Hash::make($value);
    // }
}
