<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tashtiba;
use Illuminate\Support\Facades\Storage;

class TashtibaController extends Controller
{
    public function index()
    {
        $services = Tashtiba::all()->map(function ($service) {
            $service->image_url = asset('storage/tashtiba/' . $service->image); // رابط الصورة
            return $service;
        });

        return response()->json($services);
    }


    public function show($id)
    {
        $service = Tashtiba::find($id);
        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        $service->image_url = asset('storage/tashtiba/' . $service->image); // رابط الصورة

        return response()->json($service);
    }
    public function store(Request $request)
    {
        // تحقق من صحة البيانات
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'rooms' => 'required|string',
            'price' => 'required|numeric',
            'features' => 'required|string',
            'duration' => 'required|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // السماح فقط بالصور
        ]);

        // حفظ الصورة في مجلد storage/app/public/tashtiba
        $imagePath = $request->file('image')->store('tashtiba', 'public');

        // استخراج اسم الملف فقط بدون المسار
        $imageName = basename($imagePath);

        // حفظ البيانات في قاعدة البيانات
        $tashtiba = Tashtiba::create([
            'name' => $request->name,
            'description' => $request->description,
            'rooms' => $request->rooms,
            'price' => $request->price,
            'features' => $request->features,
            'duration' => $request->duration,
            'image' => $imageName, // حفظ اسم الصورة فقط
        ]);

        return response()->json([
            'message' => 'Tashtiba service created successfully!',
            'data' => $tashtiba
        ], 201);
    }
}
