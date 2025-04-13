<?php

namespace App\Http\Controllers;

use App\Models\OrderService;
use App\Models\ServiceType;
use App\Models\PaymentMethod;
use App\Models\Technician;
use App\Notifications\NewServiceNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;

class OrderServiceController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_type_id' => 'required|exists:service_types,id',
            'description' => 'nullable|string|max:500',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'date' => 'required|date|after_or_equal:today',
            'time_slot_id' => 'required|exists:time_slots,id',
            'is_urgent' => 'required|boolean',
            'address' => 'required|string|max:255',
            'payment_method_id' => 'required|exists:payment_methods,id', // تغيير إلى payment_method_id
        ]);

        $serviceType = ServiceType::findOrFail($validated['service_type_id']);
        $paymentMethod = PaymentMethod::findOrFail($validated['payment_method_id']); // تغيير من where إلى findOrFail

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('images', 'public');
        }

        $orderService = OrderService::create([
            'service_type_id' => $serviceType->id,
            'description' => $validated['description'] ?? null,
            'image' => $imagePath,
            'date' => $validated['date'],
            'time_slot_id' => $validated['time_slot_id'],
            'is_urgent' => $validated['is_urgent'],
            'address' => $validated['address'],
            'payment_method_id' => $paymentMethod->id, // حفظ الـ id
            'status' => 'confirmed',
        ]);

        $technician = Technician::where('occupation', $serviceType->name)->first();
        if ($technician) {
            Notification::send($technician, new NewServiceNotification($orderService));
        }

        $orderService->load('serviceType', 'paymentMethod', 'timeSlot');

        return response()->json([
            'status' => 201,
            'message' => 'شكرًا لك، حجزك تم بنجاح',
            'order_details' => [
                'service_name' => $orderService->serviceType->name,
                'description' => $orderService->description,
                'image' => $orderService->image ? Storage::url($orderService->image) : null,
                'date' => $orderService->date,
                'time_slot' => $orderService->timeSlot->name,
                'is_urgent' => $orderService->is_urgent ? 'طلب فوري' : 'طلب عادي',
                'address' => $orderService->address,
                'payment_method' => $orderService->paymentMethod->name, // عرض الاسم من العلاقة
                'created_at' => $orderService->created_at,
                'updated_at' => $orderService->updated_at,
            ]
        ], 201);
    }

    public function index()
    {
        $orderServices = OrderService::with(['serviceType', 'paymentMethod', 'timeSlot'])->get();

        $formattedOrders = $orderServices->map(function ($order) {
            return [
                'id' => $order->id,
                'service_name' => $order->serviceType->name,
                'description' => $order->description,
                'image' => $order->image ? Storage::url($order->image) : null,
                'date' => $order->date,
                'time_slot' => $order->timeSlot->name,
                'is_urgent' => $order->is_urgent ? 'طلب فوري' : 'طلب عادي',
                'address' => $order->address,
                'payment_method' => $order->paymentMethod->name, // عرض الاسم من العلاقة
                'status' => $order->status,
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
            ];
        });

        return response()->json($formattedOrders);
    }
}
