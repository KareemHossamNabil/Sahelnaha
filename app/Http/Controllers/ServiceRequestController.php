<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestSchedule;
use App\Models\ServiceRequestAddress;
use App\Models\Technician;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Notifications\NewServiceRequestNotification;

class ServiceRequestController extends Controller
{
    // الخطوة الأولى: حفظ بيانات الطلب + الصورة
    public function storeStepOne(Request $request)
    {
        $request->validate([
            'service_name' => 'required|string',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('service-requests', 'public');
        }

        $serviceRequest = ServiceRequest::create([
            'user_id' => Auth::id(),
            'service_name' => $request->service_name,
            'description' => $request->description,
            'images' => $imagePath,
        ]);

        return response()->json(["request_id" => $serviceRequest->id], 201);
    }

    // الخطوة التانية: تحديد الموعد
    public function storeStepTwo(Request $request, $id)
    {
        $request->validate([
            'date' => 'required|date',
            'day' => 'required|string',
            'time_range' => 'required|string'
        ]);

        ServiceRequestSchedule::create([
            'service_request_id' => $id,
            'date' => $request->date,
            'day' => $request->day,
            'time_range' => $request->time_range,
        ]);

        return response()->json(["msg" => "Schedule added successfully"]);
    }

    // الخطوة التالتة: العنوان + طريقة الدفع + التأكيد
    public function storeStepThree(Request $request, $id)
    {
        $request->validate([
            'payment_method' => 'required|string',
            'address' => 'required|string',
        ]);

        $address = ServiceRequestAddress::create([
            'service_request_id' => $id,
            'payment_method' => $request->payment_method, // ✅ الأول
            'address' => $request->address,               // ✅ بعده
        ]);

        $serviceRequest = ServiceRequest::with('user')->find($id);
        $schedule = ServiceRequestSchedule::where('service_request_id', $id)->first();

        $technicians = Technician::all();  // جلب جميع الفنيين
        foreach ($technicians as $technician) {
            $technician->user->notify(new NewServiceRequestNotification($serviceRequest));  // إرسال الإشعار
        }
        return response()->json([
            "status" => 201,
            "message" => "تم تأكيد الحجز بنجاح",
            "service_request" => [
                "service_request_id" => $id,
                "user_id" => $serviceRequest->user_id,
                "user_name" => $serviceRequest->user->first_name . ' ' . $serviceRequest->user->last_name, // دمج first_name و last_name
                "service_name" => $serviceRequest->service_name,
                "description" => $serviceRequest->description, // إضافة description
                "images" => $serviceRequest->images,           // إضافة images
                "day" => $schedule->day,
                "date" => $schedule->date,
                "time" => $schedule->time_range,
                "payment" => [
                    "method" => $request->payment_method
                ],
                "address" => $request->address,
            ]
        ]);
    }


    // إرجاع كل الـ service_requests مع البيانات المطلوبة
    public function index()
    {
        $serviceRequests = ServiceRequest::with(['schedule', 'address', 'user'])
            ->get()
            ->map(function ($request) {
                return [
                    'service_request_id' => $request->id,
                    'user_id' => $request->user_id,
                    'user_name' => $request->user->first_name . ' ' . $request->user->last_name,
                    'service_name' => $request->service_name,
                    'description' => $request->description,
                    'images' => $request->images,
                    'day' => optional($request->schedule)->day,
                    'date' => optional($request->schedule)->date,
                    'time' => optional($request->schedule)->time_range,
                    'is_urgent' => $request->is_urgent,
                    'payment_method' => $request->payment_method,
                    'address' => optional($request->address)->address,
                ];
            });

        return response()->json([
            'status' => 200,
            'data' => $serviceRequests,
        ]);
    }
}
