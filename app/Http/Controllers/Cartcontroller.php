<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class CartController extends Controller
{
    // ✅ استرجاع جميع العناصر في السلة
    public function index()
    {
        $cartItems = CartItem::where('user_id', Auth::id())
            ->with('product') // جلب بيانات المنتج
            ->get();

        return response()->json($cartItems);
    }

    // ✅ إضافة منتج للسلة
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $cartItem = CartItem::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'product_id' => $request->product_id,
            ],
            [
                'quantity' => DB::raw("quantity + {$request->quantity}")

            ]
        );

        return response()->json(['message' => 'تمت إضافة المنتج إلى السلة بنجاح', 'cart_item' => $cartItem], 201);
    }

    // ✅ تحديث الكمية
    public function update(Request $request, $id)
    {
        $request->validate(['quantity' => 'required|integer|min:1']);

        $cartItem = CartItem::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$cartItem) {
            return response()->json(['message' => 'العنصر غير موجود في السلة'], 404);
        }

        $cartItem->update(['quantity' => $request->quantity]);

        return response()->json(['message' => 'تم تحديث الكمية بنجاح', 'cart_item' => $cartItem]);
    }

    // ✅ حذف منتج من السلة
    public function destroy($id)
    {
        $cartItem = CartItem::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$cartItem) {
            return response()->json(['message' => 'العنصر غير موجود في السلة'], 404);
        }

        $cartItem->delete();

        return response()->json(['message' => 'تم حذف العنصر من السلة بنجاح']);
    }

    // ✅ إفراغ السلة بالكامل
    public function clearCart()
    {
        CartItem::where('user_id', Auth::id())->delete();
        return response()->json(['message' => 'تم إفراغ السلة بنجاح']);
    }
}
