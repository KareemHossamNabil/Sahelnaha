<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    public function index()
    {
        $services = Service::select('id', 'service_name', 'description', 'image_path')->get();

        $services = $services->map(function ($service) {
            $service->image_path = url($service->image_path);
            return $service;
        });

        return response()->json([
            'status' => 'success',
            'data' => $services
        ]);
    }

    public function filterServices(Request $request)
    {
        $category = $request->query('category', '');

        $services = Service::where('category', 'like', "%$category%")->get();

        $services = $services->map(function ($service) {
            return [
                'id' => $service->id,
                'image' => base64_encode(file_get_contents(public_path($service->image_path))), // تحويل الصورة إلى Base64
                'image_path' => $service->image_path ? url($service->image_path) : null,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $services
        ]);
    }
}
