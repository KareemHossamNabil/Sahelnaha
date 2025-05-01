<?php
// app/Http/Controllers/OrderServiceController.php

namespace App\Http\Controllers;

use App\Models\OrderService;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class OrderServiceController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'service_name' => 'required|string',
            'category' => 'required|string',
            'date' => 'required|date',
            'time_slot' => 'required|string',
            'address' => 'required|string',
            'long' => 'required|numeric',
            'lat' => 'required|numeric',
            'payment_method' => 'required|string',
        ]);

        $service = Service::where('service_name', $request->service_name)
            ->where('category', $request->category)
            ->first();

        if (!$service) {
            return response()->json(['message' => 'Service not found.'], 404);
        }

        $user = Auth::user();

        $order = OrderService::create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'category' => $request->category,
            'date' => $request->date,
            'time_slot' => $request->time_slot,
            'address' => $request->address,
            'long' => $request->long,
            'lat' => $request->lat,
            'payment_method' => $request->payment_method,
        ]);


        $carbonDate = Carbon::parse($order->date);
        $dayName = $this->getArabicDayName($carbonDate->dayOfWeek);


        $orderArray = $order->toArray();
        $modifiedOrder = [];
        foreach ($orderArray as $key => $value) {
            $modifiedOrder[$key] = $value;

            if ($key === 'user_id') {
                $modifiedOrder['user_name'] = $user->first_name . ' ' . $user->last_name;
            }
        }

        $modifiedOrder['day'] = $dayName;

        return response()->json([
            'message' => 'Order created successfully',
            'order' => $modifiedOrder
        ], 201);
    }

    private function getArabicDayName($dayNumber)
    {
        $days = [
            'الأحد',
            'الإثنين',
            'الثلاثاء',
            'الأربعاء',
            'الخميس',
            'الجمعة',
            'السبت'
        ];

        return $days[$dayNumber];
    }
}
