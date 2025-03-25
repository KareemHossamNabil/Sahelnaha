<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\OrderService;
use App\Models\ServiceType;
use App\Models\PaymentMethod;
use App\Models\Technician;
use App\Notifications\NewOrderAssigned;
use App\Notifications\NewServiceNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;

class OrderServiceController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_type_name' => 'required|exists:service_types,name',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'date' => 'required|date',
            'time_slot' => 'required|string',
            'is_urgent' => 'required|boolean',
            'address' => 'required|string',
            'payment_method_key' => 'required|in:mastercard,vodafone_cash',
        ]);

        $serviceType = ServiceType::where('name', $validated['service_type_name'])->first();
        $paymentMethod = PaymentMethod::where('key', $validated['payment_method_key'])->first();

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('images', 'public');
        }

        $orderService = OrderService::create([
            'service_type_id' => $serviceType->id,
            'description' => $validated['description'] ?? null,
            'image' => $imagePath,
            'date' => $validated['date'],
            'time_slot' => $validated['time_slot'],
            'is_urgent' => $validated['is_urgent'],
            'address' => $validated['address'],
            'payment_method_id' => $paymentMethod->id,
            'status' => 'confirmed',
        ]);

        // اختيار فني بناءً على المهنة (افتراض أن اسم الخدمة مرتبط بالمهنة)
        $technician = Technician::where('occupation', $serviceType->name)->first(); // أول فني مهنته تطابق نوع الخدمة
        if ($technician) {
            Notification::send($technician, new NewServiceNotification($orderService));
        }

        return response()->json([
            'status' => 201,
            'message' => 'شكرًا لك، حجزك تم بنجاح',
            'order_details' => [
                'service_name' => $serviceType->name,
                'description' => $orderService->description,
                'image' => $orderService->image ? Storage::url($orderService->image) : null,
                'date' => $orderService->date,
                'time_slot' => $orderService->time_slot,
                'is_urgent' => $orderService->is_urgent ? 'طلب فوري' : 'طلب عادي',
                'address' => $orderService->address,
                'payment_method' => $paymentMethod->name,
                'created_at' => $orderService->created_at,
                'updated_at' => $orderService->updated_at,
            ]
        ]);
    }

    public function index()
    {
        $orderServices = OrderService::with(['serviceType', 'paymentMethod'])->get();
        return response()->json($orderServices);
    }
}
