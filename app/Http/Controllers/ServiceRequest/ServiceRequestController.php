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
            'scheduled_date' => 'required|date|after_or_equal:today',
            'time_slot_id' => 'required|exists:time_slots,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'address' => 'nullable|string', // العنوان اختياري لأننا سنستخدم عنوان المستخدم إذا لم يتم توفيره
            'is_urgent' => 'boolean',
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'use_user_address' => 'boolean', // إضافة حقل للتحكم في استخدام عنوان المستخدم
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $validator->errors()
            ], 422);
        }

        // الحصول على المستخدم الحالي
        $user = Auth::user();

        // معالجة الصور إذا وجدت
        $images = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('service_requests', 'public');
                $images[] = $path;
            }
        }

        // تحديد العنوان
        $address = $request->address;

        // إذا لم يتم توفير عنوان أو طلب المستخدم استخدام عنوانه الأساسي
        if (empty($address) || ($request->use_user_address ?? true)) {
            $address = $user->address;
        }

        // إنشاء طلب الخدمة
        $serviceRequest = ServiceRequest::create([
            'user_id' => $user->id,
            'service_type_id' => $request->service_type_id,
            'description' => $request->description,
            'scheduled_date' => $request->scheduled_date,
            'time_slot_id' => $request->time_slot_id,
            'payment_method_id' => $request->payment_method_id,
            'address' => $address, // استخدام العنوان المحدد
            'is_urgent' => $request->is_urgent ?? false,
            'images' => $images,
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

        // التحقق من أن المستخدم هو صاحب الطلب
        if (Auth()::id() !== $serviceRequest->user_id) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك بتعديل هذا الطلب'
            ], 403);
        }

        // التحقق من أن الطلب لا يزال في حالة معلق
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
            'time_slot_id' => 'sometimes|exists:time_slots,id',
            'payment_method_id' => 'sometimes|exists:payment_methods,id',
            'address' => 'nullable|string',
            'is_urgent' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $validator->errors()
            ], 422);
        }

        // تحديث طلب الخدمة
        $serviceRequest->update($request->all());

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

        return response()->json([
            'status' => true,
            'message' => 'تم استرجاع العنوان بنجاح',
            'data' => [
                'address' => $user->address
            ]
        ]);
    }

    // باقي الدوال...
}
