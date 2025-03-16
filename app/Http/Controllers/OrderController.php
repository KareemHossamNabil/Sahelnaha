<?php


namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        // استقبال البيانات بصيغة JSON
        $validated = $request->validate([
            'task_type'      => 'required|string',
            'description'    => 'nullable|string',
            'image'          => 'nullable|string', // سيتم استقباله كـ Base64
            'is_urgent'      => 'required|boolean',
            'time_slot'      => 'required|string',
            'address'        => 'required|string',
            'payment_method' => 'required|string|in:MasterCard,كاش',
        ]);

        // تحديد المستخدم الحالي
        $validated['user_id'] = Auth::id();

        // معالجة الصورة (إذا تم إرسالها)
        if ($request->has('image')) {
            $imageData = $request->input('image'); // استقبال Base64
            $imageName = 'orders/' . uniqid() . '.jpg'; // تحديد اسم الصورة

            // حفظ الصورة في Storage
            Storage::disk('public')->put($imageName, base64_decode($imageData));

            // تخزين المسار في قاعدة البيانات
            $validated['image'] = $imageName;
        }

        // إنشاء الطلب في قاعدة البيانات
        $order = Order::create($validated);

        // إرسال إشعار للفني
        event(new \App\Events\OrderCreated($order));

        // إرجاع استجابة JSON
        return response()->json([
            'status' => 201,
            'msg'    => 'تم إرسال الطلب بنجاح',
            'order'  => $order
        ], 201);
    }
}
