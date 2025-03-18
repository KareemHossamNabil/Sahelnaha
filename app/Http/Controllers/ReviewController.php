<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    // إضافة تقييم جديد للمنتج
    public function store(Request $request, $id)
    {
        // التحقق من وجود المنتج
        $product = Product::find($id);
        if (!$product) {
            return response()->json([
                'status' => 404,
                'msg' => 'Product not found'
            ], 404);
        }

        // التحقق من صحة البيانات المدخلة
        $request->validate([
            'user_name' => 'required|string|max:255',
            'comment' => 'required|string',
            'rating' => 'required|numeric|min:1|max:5'
        ]);

        // إضافة التقييم الجديد
        Review::create([
            'product_id' => $id,
            'user_name' => $request->user_name,
            'comment' => $request->comment,
            'rating' => $request->rating
        ]);

        // تحديث متوسط التقييم في جدول products
        $averageRating = Review::where('product_id', $id)->avg('rating');
        $product->update(['rating' => round($averageRating, 1)]);

        return response()->json([
            'status' => 201,
            'msg' => 'Review added successfully',
            'new_rating' => round($averageRating, 1)
        ], 201);
    }
}
