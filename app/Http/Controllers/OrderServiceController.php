<?php

// app/Http/Controllers/OrderServiceController.php

namespace App\Http\Controllers;

use App\Models\OrderService;
use App\Models\Service;
use App\Models\Technician;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\User;
use App\Notifications\OrderServiceNotification;

class OrderServiceController extends Controller
{
    public function store(Request $request)
    {
        // التحقق من البيانات المدخلة
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

        // جلب الخدمة المطلوبة
        $service = Service::where('service_name', $request->service_name)
            ->where('category', $request->category)
            ->first();

        // إذا لم يتم العثور على الخدمة
        if (!$service) {
            return response()->json(['message' => 'Service not found.'], 404);
        }

        // جلب بيانات المستخدم
        $user = Auth::user();

        // إنشاء الطلب
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

        // تحديد اسم اليوم بناءً على التاريخ
        $carbonDate = Carbon::parse($order->date);
        $dayName = $this->getArabicDayName($carbonDate->dayOfWeek);

        // ترتيب البيانات
        $orderArray = $order->toArray();
        $modifiedOrder = [];

        foreach ($orderArray as $key => $value) {
            $modifiedOrder[$key] = $value;

            // إضافة اسم المستخدم بعد الـ user_id
            if ($key === 'user_id') {
                $modifiedOrder['user_name'] = $user->first_name . ' ' . $user->last_name;
            }
        }

        // إضافة اليوم
        $modifiedOrder['day'] = $dayName;

        // ترتيب الـ response
        $response = [
            'id' => $modifiedOrder['id'],
            'user_id' => $modifiedOrder['user_id'],
            'user_name' => $modifiedOrder['user_name'],
            'service_name' => $service->service_name,  // إضافة اسم الخدمة هنا
            'date' => $modifiedOrder['date'],
            'day' => $modifiedOrder['day'],
            'time_slot' => $modifiedOrder['time_slot'],
            'address' => $modifiedOrder['address'],
            'long' => $modifiedOrder['long'],
            'lat' => $modifiedOrder['lat'],
            'payment_method' => $modifiedOrder['payment_method'],
            'created_at' => $modifiedOrder['created_at'],
            'updated_at' => $modifiedOrder['updated_at'],
        ];

        // الرد مع status code 201
        return response()->json([
            'status' => 201,
            'message' => 'Order created successfully',
            'order' => $response
        ], 201);
    }

    // دالة للحصول على اسم اليوم باللغة العربية
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

    /**
     * Send notifications to technicians with matching specialties
     * 
     * @param OrderService $orderService
     * @return int Number of technicians notified
     */
    private function notifyMatchingTechnicians(OrderService $orderService)
    {
        // Get technicians whose specialty matches the service category
        $technicians = User::where('role', 'technician')
            ->where('specialty', $orderService->category)
            ->get();

        // Send notification to each matching technician
        foreach ($technicians as $technician) {
            // Store notification in database
            $technician->notify(new OrderServiceNotification($orderService));

            // Broadcast real-time event
            event(new \App\Events\NewOrderServiceEvent($orderService, $technician));
        }

        return $technicians->count();
    }
}
