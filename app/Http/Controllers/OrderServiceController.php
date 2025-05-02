<?php

namespace App\Http\Controllers;

use App\Models\OrderService;
use App\Models\Service;
use App\Models\Technician;
use App\Models\TechnicianNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\User;
use App\Notifications\OrderServiceNotification;
use Illuminate\Support\Facades\Http;
use OrderServiceNotification as GlobalOrderServiceNotification;

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
            'payment_method' => 'required|string',
            'is_urgent' => 'nullable|boolean',
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

        // جلب الإحداثيات بناءً على العنوان
        $coordinates = $this->getCoordinatesFromAddress($request->address);

        // إنشاء الطلب
        $order = OrderService::create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'category' => $request->category,
            'date' => $request->date,
            'time_slot' => $request->time_slot,
            'address' => $request->address,
            'long' => $coordinates['longitude'],
            'lat' => $coordinates['latitude'],
            'payment_method' => $request->payment_method,
            'is_urgent' => $request->is_urgent ?? false,
        ]);

        // تحديد اسم اليوم بناءً على التاريخ
        $carbonDate = Carbon::parse($order->date);
        $dayName = $this->getArabicDayName($carbonDate->dayOfWeek);

        // إضافة اليوم للطلب
        $order->day = $dayName;

        // إرسال الإشعارات للفنيين الذين لديهم تطابق مع الفئة
        $technicians = Technician::where('specialty', $request->category)->get();
        foreach ($technicians as $technician) {
            $technician->notify(new OrderServiceNotification([
                'id' => $order->id,
                'user_id' => $user->id,
                'user_name' => $user->first_name . ' ' . $user->last_name,
                'service_name' => $service->service_name,
                'category' => $order->category,
                'date' => $order->date,
                'day' => $dayName,
                'time_slot' => $order->time_slot,
                'address' => $order->address,
                'is_urgent' => $order->is_urgent,
            ]));
        }

        // إنشاء رابط الخريطة
        $mapUrl = $this->generateMapUrl($coordinates['latitude'], $coordinates['longitude']);

        return response()->json([
            'status' => 201,
            'message' => 'تم إنشاء الطلب بنجاح',
            'data' => [
                'service_request' => [
                    'id' => $order->id,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->first_name . ' ' . $user->last_name,
                    ],
                    'service_name' => $service->service_name,
                    'category' => $order->category,
                    'schedule' => [
                        'date' => $order->date,
                        'day' => $dayName,
                        'time_slot' => $order->time_slot,
                    ],
                    'is_urgent' => $order->is_urgent,
                    'payment_method' => $order->payment_method,
                    'address' => $order->address,
                ],
                'location' => [
                    'longitude' => $coordinates['longitude'],
                    'latitude' => $coordinates['latitude'],
                    'map_url' => $mapUrl
                ]
            ]
        ], 201);
    }

    public function index()
    {
        $orders = OrderService::with(['user', 'service'])->get()->map(function ($order) {
            // تحديد اسم اليوم بناءً على التاريخ
            $carbonDate = Carbon::parse($order->date);
            $dayName = $this->getArabicDayName($carbonDate->dayOfWeek);

            return [
                'id' => $order->id,
                'user' => [
                    'id' => $order->user->id,
                    'name' => $order->user->first_name . ' ' . $order->user->last_name,
                ],
                'service_name' => $order->service->service_name,
                'category' => $order->category,
                'schedule' => [
                    'date' => $order->date,
                    'day' => $dayName,
                    'time_slot' => $order->time_slot,
                ],
                'is_urgent' => $order->is_urgent,
                'payment_method' => $order->payment_method,
                'address' => $order->address,
                'location' => [
                    'longitude' => $order->long,
                    'latitude' => $order->lat,
                    'map_url' => $this->generateMapUrl($order->lat, $order->long)
                ]
            ];
        });

        return response()->json([
            'status' => 200,
            'data' => $orders
        ]);
    }

    // دالة للحصول على الإحداثيات من العنوان باستخدام Nominatim API (OpenStreetMap)
    private function getCoordinatesFromAddress($address)
    {
        try {
            // OpenStreetMap Nominatim API URL
            $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($address);

            // إضافة User-Agent header (مطلوب من Nominatim)
            $response = Http::withHeaders([
                'User-Agent' => 'Laravel/ServiceApp'
            ])->get($url);

            $data = $response->json();

            if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
                $latitude = $data[0]['lat'];
                $longitude = $data[0]['lon'];

                return [
                    'latitude' => $latitude,
                    'longitude' => $longitude
                ];
            }

            // محاولة ثانية باستخدام صيغة عنوان مبسطة
            $simplifiedAddress = preg_replace('/[^a-zA-Z0-9\s]/', '', $address);
            $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($simplifiedAddress);

            $response = Http::withHeaders([
                'User-Agent' => 'Laravel/ServiceApp'
            ])->get($url);

            $data = $response->json();

            if (!empty($data) && isset($data[0]['lat']) && isset($data[0]['lon'])) {
                $latitude = $data[0]['lat'];
                $longitude = $data[0]['lon'];

                return [
                    'latitude' => $latitude,
                    'longitude' => $longitude
                ];
            }

            // في حالة عدم العثور على إحداثيات، استخدم إحداثيات افتراضية
            // يمكن تعديل هذه الإحداثيات لتكون مركز المدينة التي تعمل فيها الخدمة
            return [
                'latitude' => '24.7136', // إحداثيات الرياض كمثال
                'longitude' => '46.6753'
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Geocoding error: ' . $e->getMessage());

            // في حالة حدوث خطأ، استخدم إحداثيات افتراضية
            return [
                'latitude' => '24.7136', // إحداثيات الرياض كمثال
                'longitude' => '46.6753'
            ];
        }
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
     * Generate a map URL for the given coordinates
     */
    private function generateMapUrl($latitude, $longitude)
    {
        return "https://www.openstreetmap.org/?mlat={$latitude}&mlon={$longitude}#map=18/{$latitude}/{$longitude}";
    }

    /**
     * Mark an order service as completed
     */
    public function completeOrder(Request $request, $id)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string',
        ]);

        $order = OrderService::findOrFail($id);

        // Check if the order belongs to the authenticated user
        if ($order->user_id !== Auth::id()) {
            return response()->json(['message' => 'غير مصرح لك بهذه العملية'], 403);
        }

        // Check if the order is in progress
        if ($order->status !== 'in_progress') {
            return response()->json(['message' => 'لا يمكن إكمال هذا الطلب في حالته الحالية'], 400);
        }

        // Update order status
        $order->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Get the technician
        $technician = Technician::findOrFail($order->technician_id);

        // Update technician rating
        $technician->updateRating($request->rating);

        // Save review if provided
        if ($request->has('review')) {
            \App\Models\Review::create([
                'user_id' => Auth::id(),
                'technician_id' => $technician->id,
                'order_service_id' => $order->id,
                'rating' => $request->rating,
                'review' => $request->review,
            ]);
        }
    }
}
