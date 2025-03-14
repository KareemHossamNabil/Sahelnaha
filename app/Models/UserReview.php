<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserReview extends Model
{
    use HasFactory;

    protected $table = 'users_reviews'; // اسم الجدول في الداتابيز
    protected $fillable = ['username', 'review']; // الأعمدة اللي نقدر نضيف فيها بيانات
}
