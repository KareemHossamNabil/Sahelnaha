<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductReviewController extends Controller
{
    public function store(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json([
                'status' => 404,
                'msg' => 'Product not found'
            ], 404);
        }

        $request->validate([
            'comment' => 'required|string',
            'rating' => 'required|numeric|min:1|max:5'
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'status' => 401,
                'msg' => 'Unauthorized. Please login first.'
            ], 401);
        }

        $review = Review::create([
            'product_id' => $id,
            'user_name' => $user->first_name . ' ' . $user->last_name,
            'comment' => $request->comment,
            'rating' => $request->rating
        ]);

        $averageRating = Review::where('product_id', $id)->avg('rating');
        $product->update(['rating' => round($averageRating, 1)]);

        return response()->json([
            'status' => 201,
            'msg' => 'Review added successfully',
            'new_rating' => round($averageRating, 1),
            'review' => $review
        ], 201);
    }
}
