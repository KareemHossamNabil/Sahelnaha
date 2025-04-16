<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceType extends Model
{
    use HasFactory;

    protected $table = 'service_types';

    protected $fillable = [
        'name_en',
        'name_ar',
        'category',
        'icon',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // العلاقة مع جدول الخدمات إذا كان موجود
    public function services()
    {
        return $this->hasMany(Service::class, 'service_type_id');
    }

    // دالة للحصول على الاسم حسب اللغة
    public function getNameAttribute()
    {
        $locale = app()->getLocale();
        return $locale == 'ar' ? $this->name_ar : $this->name_en;
    }
}
