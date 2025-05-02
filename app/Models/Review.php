<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    // 👇 إضافة اسم الجدول الجديد صراحة
    protected $table = 'products_reviews';

    protected $fillable = ['product_id', 'user_name', 'comment', 'rating'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
