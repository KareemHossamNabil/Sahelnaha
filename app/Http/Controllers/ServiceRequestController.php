<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServiceRequest;
use App\Models\Technician;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Notifications\ServiceRequestNotification;

class ServiceRequestController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_name' => 'required|string',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'is_urgent' => 'nullable|boolean',

            'date' => 'required|date',
            'day' => 'required|string',
            'time_slot' => 'required|string',

            'payment_method' => 'required|string',
            'address' => 'required|string',
        ]);

        // استخراج الإحداثيات من العنوان
        $location = $this->getLatLongFromAddress($validated['address']);
        if (!$location) {
            return response()->json([
                'status' => 422,
                'message' => 'فشل في تحديد الموقع من العنوان'
            ], 422);
        }

        // رفع الصورة
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('service-requests', 'public');
        }

        // إنشاء الطلب
        $serviceRequest = ServiceRequest::create([
            'user_id' => Auth::id(),
            'service_name' => $validated['service_name'],
            'description' => $validated['description'],
            'images' => $imagePath,
            'is_urgent' => $validated['is_urgent'] ?? false,
            'date' => $validated['date'],
            'day' => $validated['day'],
            'time_slot' => $validated['time_slot'],
            'payment_method' => $validated['payment_method'],
            'address' => $validated['address'],
            'longitude' => $location['lng'],
            'latitude' => $location['lat'],
        ]);

        // إرسال إشعارات للفنيين
        $technicians = Technician::all();
        foreach ($technicians as $technician) {
            $technician->notify(new ServiceRequestNotification($serviceRequest));
        }

        return response()->json([
            'status' => 201,
            'message' => 'تم إنشاء الطلب بنجاح',
            'data' => [
                'service_request' => $serviceRequest,
                'location' => [
                    'longitude' => $serviceRequest->longitude,
                    'latitude' => $serviceRequest->latitude,
                    'map_url' => $this->generateMapUrl($serviceRequest->latitude, $serviceRequest->longitude)
                ]
            ]
        ]);
    }

    public function index()
    {
        $serviceRequests = ServiceRequest::with('user')->get()->map(function ($request) {
            return [
                'id' => $request->id,
                'user' => [
                    'id' => $request->user->id,
                    'name' => $request->user->first_name . ' ' . $request->user->last_name,
                ],
                'service_name' => $request->service_name,
                'description' => $request->description,
                'images' => $request->images,
                'schedule' => [
                    'date' => $request->date,
                    'day' => $request->day,
                    'time_slot' => $request->time_slot,
                ],
                'is_urgent' => $request->is_urgent,
                'payment_method' => $request->payment_method,
                'address' => $request->address,
                'location' => [
                    'longitude' => $request->longitude,
                    'latitude' => $request->latitude,
                    'map_url' => $this->generateMapUrl($request->latitude, $request->longitude)
                ]
            ];
        });

        return response()->json([
            'status' => 200,
            'data' => $serviceRequests
        ]);
    }

    private function generateMapUrl($latitude, $longitude)
    {
        return "https://www.openstreetmap.org/?mlat={$latitude}&mlon={$longitude}#map=18/{$latitude}/{$longitude}";
    }

    private function getLatLongFromAddress($address)
    {
        $url = "https://nominatim.openstreetmap.org/search";

        $response = Http::withHeaders([
            'User-Agent' => 'LaravelApp/1.0'
        ])->get($url, [
            'q' => $address,
            'format' => 'json',
            'limit' => 1
        ]);

        if ($response->successful() && isset($response[0])) {
            return [
                'lat' => $response[0]['lat'],
                'lng' => $response[0]['lon']
            ];
        }

        return null;
    }
}
