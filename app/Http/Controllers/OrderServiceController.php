<?php

namespace App\Http\Controllers;

use App\Models\ServiceRequest;
use App\Models\Service;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use App\Notifications\ServiceRequestNotification;
use Illuminate\Support\Facades\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

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

        // إنشاء طلب الخدمة
        $serviceRequest = ServiceRequest::create([
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
        $carbonDate = Carbon::parse($serviceRequest->date);
        $dayName = $this->getArabicDayName($carbonDate->dayOfWeek);

        // إضافة اليوم للطلب
        $serviceRequest->day = $dayName;

        // إرسال الإشعارات للفنيين الذين لديهم تطابق مع الفئة
        $technicians = Technician::where('specialty', $request->category)->get();
        foreach ($technicians as $technician) {
            // إعداد البيانات للإشعار
            $notificationData = [
                'id' => $serviceRequest->id,
                'user_id' => $user->id,
                'user_name' => $user->first_name . ' ' . $user->last_name,
                'service_name' => $service->service_name,
                'category' => $serviceRequest->category,
                'date' => $serviceRequest->date,
                'day' => $dayName,
                'time_slot' => $serviceRequest->time_slot,
                'address' => $serviceRequest->address,
                'is_urgent' => $serviceRequest->is_urgent,
            ];

            // إرسال الإشعار عبر FCM
            $this->sendFCMNotification($technician, $notificationData);

            // إرسال إشعار عبر Laravel notifications (يمكنك إزالة هذا إذا كنت تستخدم FCM فقط)
            $technician->notify(new ServiceRequestNotification($notificationData));
        }

        // إنشاء رابط الخريطة
        $mapUrl = $this->generateMapUrl($coordinates['latitude'], $coordinates['longitude']);

        return response()->json([
            'status' => 201,
            'message' => 'تم إنشاء الطلب بنجاح',
            'data' => [
                'service_request' => [
                    'id' => $serviceRequest->id,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->first_name . ' ' . $user->last_name,
                    ],
                    'service_name' => $service->service_name,
                    'category' => $serviceRequest->category,
                    'schedule' => [
                        'date' => $serviceRequest->date,
                        'day' => $dayName,
                        'time_slot' => $serviceRequest->time_slot,
                    ],
                    'is_urgent' => $serviceRequest->is_urgent,
                    'payment_method' => $serviceRequest->payment_method,
                    'address' => $serviceRequest->address,
                ],
                'location' => [
                    'longitude' => $coordinates['longitude'],
                    'latitude' => $coordinates['latitude'],
                    'map_url' => $mapUrl
                ]
            ]
        ], 201);
    }

    // دالة لإرسال إشعار عبر FCM
    private function sendFCMNotification($technician, $data)
    {
        // التأكد من أن الفني لديه Token
        if ($technician->fcm_token) {
            $messaging = app('firebase.messaging');
            $message = CloudMessage::withTarget('token', $technician->fcm_token)
                ->withNotification(FirebaseNotification::create(
                    'طلب خدمة جديد',
                    'خدمة جديدة في الفئة ' . $data['category'] . ' في ' . $data['time_slot']
                ))
                ->withData($data);

            // إرسال الإشعار
            $messaging->send($message);
        }
    }

    public function index()
    {
        $serviceRequests = ServiceRequest::with(['user', 'service'])->get()->map(function ($serviceRequest) {
            // تحديد اسم اليوم بناءً على التاريخ
            $carbonDate = Carbon::parse($serviceRequest->date);
            $dayName = $this->getArabicDayName($carbonDate->dayOfWeek);

            return [
                'id' => $serviceRequest->id,
                'user' => [
                    'id' => $serviceRequest->user->id,
                    'name' => $serviceRequest->user->first_name . ' ' . $serviceRequest->user->last_name,
                ],
                'service_name' => $serviceRequest->service->service_name,
                'category' => $serviceRequest->category,
                'schedule' => [
                    'date' => $serviceRequest->date,
                    'day' => $dayName,
                    'time_slot' => $serviceRequest->time_slot,
                ],
                'is_urgent' => $serviceRequest->is_urgent,
                'payment_method' => $serviceRequest->payment_method,
                'address' => $serviceRequest->address,
                'location' => [
                    'longitude' => $serviceRequest->long,
                    'latitude' => $serviceRequest->lat,
                    'map_url' => $this->generateMapUrl($serviceRequest->lat, $serviceRequest->long)
                ]
            ];
        });

        return response()->json([
            'status' => 200,
            'data' => $serviceRequests
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
                return [
                    'latitude' => $data[0]['lat'],
                    'longitude' => $data[0]['lon']
                ];
            }

            // في حالة عدم العثور على إحداثيات، استخدم إحداثيات افتراضية
            return [
                'latitude' => '24.7136', // إحداثيات الرياض كمثال
                'longitude' => '46.6753'
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Geocoding error: ' . $e->getMessage());
            return [
                'latitude' => '24.7136',
                'longitude' => '46.6753'
            ];
        }
    }

    // دالة للحصول على اسم اليوم باللغة العربية
    private function getArabicDayName($dayNumber)
    {
        $days = [
            'الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'
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
}
