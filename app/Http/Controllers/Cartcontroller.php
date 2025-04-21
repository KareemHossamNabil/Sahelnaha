<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class CartController extends Controller
{
    protected function calculateTotal($items)
    {
        return collect($items)->sum(function ($item) {
            $priceAfterDiscount = $item['price'] * (1 - $item['discount'] / 100);
            return $priceAfterDiscount * $item['quantity'];
        });
    }

    public function addToCart(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);
        $cart = Cache::get('cart', []);

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity']++;
        } else {
            $cart[$productId] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'rating' => $product->rating,
                'discount' => $product->discount,
                'category' => $product->category,
                'description' => $product->description,
                'image_url' => $product->image_url,
                'reviews' => $product->reviews,
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
                'quantity' => 1,
            ];
        }

        Cache::put('cart', $cart, now()->addHours(72));
        $total = $this->calculateTotal($cart);

        return response()->json([
            'status' => 200,
            'msg' => 'Product added to cart successfully',
            'cart' => $cart,
            'total' => $total
        ]);
    }

    public function viewCart()
    {
        $cart = Cache::get('cart', []);
        $total = $this->calculateTotal($cart);

        return response()->json([
            'status' => 200,
            'msg' => 'Cart retrieved successfully',
            'cart' => $cart,
            'total' => $total
        ]);
    }
    public function removeFromCart($productId)
    {
        $cart = Cache::get('cart', []);

        if (!isset($cart[$productId])) {
            return response()->json([
                'status' => 404,
                'msg' => 'Product not found in cart'
            ], 404);
        }

        if ($cart[$productId]['quantity'] > 1) {

            $cart[$productId]['quantity']--;
            $message = 'Product quantity decreased';
        } else {

            unset($cart[$productId]);
            $message = 'Product removed from cart';
        }


        if (empty($cart)) {
            Cache::forget('cart');
            return response()->json([
                'status' => 200,
                'msg' => 'Cart is now empty',
                'cart' => [],
                'total' => 0
            ]);
        }


        Cache::put('cart', $cart, now()->addHours(2));

        $total = $this->calculateTotal($cart);

        return response()->json([
            'status' => 200,
            'msg' => $message,
            'cart' => $cart,
            'total' => $total
        ]);
    }

    public function deleteProduct($productId)
    {

        $cart = Cache::get('cart', []);


        if (!isset($cart[$productId])) {
            return response()->json([
                'status' => 404,
                'msg' => 'Product not found in cart'
            ], 404);
        }


        $productName = $cart[$productId]['name'] ?? 'Product';


        unset($cart[$productId]);


        if (empty($cart)) {
            Cache::forget('cart');
            return response()->json([
                'status' => 200,
                'msg' => 'Cart is now empty',
                'cart' => [],
                'total' => 0
            ]);
        }


        Cache::put('cart', $cart, now()->addHours(2));

        $total = $this->calculateTotal($cart);

        return response()->json([
            'status' => 200,
            'msg' => "$productName has been removed from your cart",
            'cart' => $cart,
            'total' => $total
        ]);
    }

    public function clearCart()
    {
        Cache::forget('cart');

        return response()->json([
            'status' => 200,
            'msg' => 'Cart cleared successfully',
            'cart' => [],
            'total' => 0
        ]);
    }
}
