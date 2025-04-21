<?php

namespace App\Http\Controllers\ServiceRequest;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\ServiceRequestResource;
use Illuminate\Support\Facades\Auth;

class ServiceRequestController extends Controller
{
    /**
     * إنشاء طلب خدمة جديد
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_type_id' => 'required|exists:service_types,id',
            'description' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'scheduled_date' => 'required|date|after_or_equal:today',
            'is_urgent' => 'boolean',
            'time_slot_id' => 'required|exists:time_slots,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'address' => 'nullable|string',
            'use_user_address' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        // معالجة الصور ورفعها
        $imageUrls = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('service_requests', 'public');
                $imageUrls[] = asset('storage/' . $path);
            }
        }

        // تحديد العنوان
        $address = $request->address;

        if (empty($address) || ($request->use_user_address ?? true)) {
            $address = $user->address;
        }

        // إنشاء الطلب
        $serviceRequest = ServiceRequest::create([
            'user_id' => $user->id,
            'service_type_id' => $request->service_type_id,
            'description' => $request->description,
            'images' => json_encode($imageUrls),
            'scheduled_date' => $request->scheduled_date,
            'is_urgent' => $request->is_urgent ?? false,
            'time_slot_id' => $request->time_slot_id,
            'payment_method_id' => $request->payment_method_id,
            'address' => $address,
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم إنشاء طلب الخدمة بنجاح',
            'data' => new ServiceRequestResource($serviceRequest)
        ], 201);
    }

    /**
     * تحديث طلب خدمة
     */
    public function update(Request $request, $id)
    {
        $serviceRequest = ServiceRequest::findOrFail($id);

        if (Auth::id() !== $serviceRequest->user_id) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك بتعديل هذا الطلب'
            ], 403);
        }

        if ($serviceRequest->status !== 'pending') {
            return response()->json([
                'status' => false,
                'message' => 'لا يمكن تعديل الطلب بعد قبوله أو إكماله أو إلغائه'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'service_type_id' => 'sometimes|exists:service_types,id',
            'description' => 'nullable|string',
            'scheduled_date' => 'sometimes|date|after_or_equal:today',
            'is_urgent' => 'boolean',
            'time_slot_id' => 'sometimes|exists:time_slots,id',
            'payment_method_id' => 'sometimes|exists:payment_methods,id',
            'address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $validator->errors()
            ], 422);
        }

        $serviceRequest->update($request->only([
            'service_type_id',
            'description',
            'scheduled_date',
            'is_urgent',
            'time_slot_id',
            'payment_method_id',
            'address',
        ]));

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث طلب الخدمة بنجاح',
            'data' => new ServiceRequestResource($serviceRequest)
        ]);
    }

    /**
     * الحصول على عنوان المستخدم
     */
    public function getUserAddress()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'المستخدم غير مسجل دخول',
            ], 401);
        }

        // Check if the address property exists
        $address = $user->address ?? null;

        return response()->json([
            'status' => 200,
            'message' => 'تم استرجاع العنوان بنجاح',
            'data' => [
                'address' => $address
            ]
        ]);
    }

    public function index()
    {
        $user = Auth::user();

        // جلب كل الطلبات الخاصة بالمستخدم الحالي
        $requests = ServiceRequest::with(['serviceType', 'timeSlot', 'paymentMethod', 'technician'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'تم جلب طلبات الخدمة بنجاح',
            'data' => ServiceRequestResource::collection($requests),
        ]);
    }

    public function show($id)
    {
        $serviceRequest = ServiceRequest::with(['serviceType', 'timeSlot', 'paymentMethod', 'technician'])
            ->findOrFail($id);

        // التحقق من ملكية المستخدم للطلب
        if ($serviceRequest->user_id !== Auth::id()) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك بمشاهدة هذا الطلب'
            ], 403);
        }

        return response()->json([
            'status' => true,
            'message' => 'تم جلب تفاصيل الطلب بنجاح',
            'data' => new ServiceRequestResource($serviceRequest),
        ]);
    }

    public function destroy($id)
    {
        $serviceRequest = ServiceRequest::findOrFail($id);

        // التحقق من ملكية المستخدم للطلب
        if ($serviceRequest->user_id !== Auth::id()) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك بحذف هذا الطلب'
            ], 403);
        }

        // يمكن حذف الطلب فقط إذا كان في حالة pending
        if ($serviceRequest->status !== 'pending') {
            return response()->json([
                'status' => false,
                'message' => 'لا يمكن حذف الطلب بعد قبوله أو تنفيذه أو إلغائه'
            ], 400);
        }

        $serviceRequest->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم حذف طلب الخدمة بنجاح',
        ]);
    }
}
