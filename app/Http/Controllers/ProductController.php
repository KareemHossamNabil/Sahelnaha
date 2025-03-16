<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    // إرجاع جميع المنتجات
    public function index()
    {
        $products = Product::all();
        return response()->json([
            'status' => 200,
            'msg'    => 'Products retrieved successfully',
            'data'   => ProductResource::collection($products),
        ]);
    }

    // إرجاع منتج معين
    public function show($id)
    {
        $product = Product::findOrFail($id);
        return response()->json([
            'status' => 200,
            'msg'    => 'Product retrieved successfully',
            'data'   => new ProductResource($product),
        ]);
    }

    // إضافة منتج جديد مع رفع الصورة
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'price'       => 'required|numeric',
            'discount'    => 'nullable|integer|min:0|max:100',
            'category'    => 'required|string|max:255',
            'description' => 'nullable|string',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // حفظ الصورة إذا تم رفعها
        $imagePath = $request->file('image') ? $request->file('image')->store('products', 'public') : null;

        $product = Product::create([
            'name'        => $request->name,
            'price'       => $request->price,
            'discount'    => $request->discount ?? 0,
            'category'    => $request->category,
            'description' => $request->description,
            'image'       => $imagePath,
        ]);

        return response()->json([
            'status' => 201,
            'msg'    => 'Product created successfully',
            'data'   => new ProductResource($product),
        ], 201);
    }

    // تحديث المنتج
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'name'        => 'sometimes|string|max:255',
            'price'       => 'sometimes|numeric',
            'discount'    => 'sometimes|integer|min:0|max:100',
            'category'    => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // تحديث الصورة إذا تم رفع صورة جديدة
        if ($request->hasFile('image')) {
            // حذف الصورة القديمة
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $product->image = $request->file('image')->store('products', 'public');
        }

        $product->update($request->only(['name', 'price', 'discount', 'category', 'description', 'image']));

        return response()->json([
            'status' => 'success',
            'msg'    => 'Product updated successfully',
            'data'   => new ProductResource($product),
        ]);
    }

    // حذف المنتج
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // حذف الصورة من التخزين إذا كانت موجودة
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return response()->json([
            'status' => 'success',
            'msg'    => 'Product deleted successfully',
        ]);
    }
}
