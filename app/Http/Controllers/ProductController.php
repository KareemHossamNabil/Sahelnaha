<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{

    //  Retrived All Products
    public function index()
    {
        $products = Product::with('reviews')->get();

        return response()->json([
            'status' => 200,
            'msg'    => 'Products retrieved successfully',
            'data'   => ProductResource::collection($products),
        ]);
    }

    //  by ID
    public function show($id)
    {
        $product = Product::with('reviews')->find($id);

        if (!$product) {
            return response()->json([
                'status' => 404,
                'msg' => 'Product not found'
            ], 404);
        }
        return response()->json([
            'status' => 200,
            'msg'    => 'Product retrieved successfully',
            'data'   => new ProductResource($product),
        ]);
    }


    // Filteration By Category
    public function filterByCategory($category)
    {

        $products = Product::with('reviews')->where('category', $category)->get();

        if ($products->isEmpty()) {
            return response()->json([
                'status' => 404,
                'msg'    => 'No products found in this category',
                'data'   => [],
            ], 404);
        }

        // إرجاع المنتجات مع التقييمات
        return response()->json([
            'status' => 200,
            'msg'    => 'Products retrieved successfully',
            'data'   => ProductResource::collection($products),
        ]);
    }
}
