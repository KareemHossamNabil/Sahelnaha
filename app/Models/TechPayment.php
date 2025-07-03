<?php

// TechPayment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TechPayment extends Model
{
    use HasFactory;

    // تأكد من إضافة هذا العمود في الـ fillable
    protected $fillable = [
        'tech_id', 
        'technician_wallet_id', 
        'amount', 
        'type', 
        'status',
        'description', 
        'metadata',
        'reference', // أضفنا الـ reference هنا
    ];

    // يمكنك إضافة علاقات أخرى إذا كانت موجودة في التطبيق مثل علاقات مع الموديلات الأخرى
}
