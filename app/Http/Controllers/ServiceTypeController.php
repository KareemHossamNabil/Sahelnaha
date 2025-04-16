<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ServiceType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ServiceTypeController extends Controller
{
    /**
     * عرض قائمة بجميع أنواع الخدمات
     */
    public function index()
    {
        $serviceTypes = ServiceType::where('is_active', 1)->get();

        return response()->json([
            'status' => true,
            'message' => 'تم استرجاع أنواع الخدمات بنجاح',
            'data' => $serviceTypes
        ]);
    }

    /**
     * عرض نوع خدمة محدد
     */
    public function show($id)
    {
        $serviceType = ServiceType::find($id);

        if (!$serviceType) {
            return response()->json([
                'status' => false,
                'message' => 'نوع الخدمة غير موجود',
                'data' => null
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'تم استرجاع نوع الخدمة بنجاح',
            'data' => $serviceType
        ]);
    }

    /**
     * إنشاء نوع خدمة جديد
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'icon' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $validator->errors()
            ], 422);
        }

        $serviceType = ServiceType::create([
            'name_en' => $request->name_en,
            'name_ar' => $request->name_ar,
            'category' => $request->category,
            'icon' => $request->icon,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم إنشاء نوع الخدمة بنجاح',
            'data' => $serviceType
        ], 201);
    }

    /**
     * تحديث نوع خدمة
     */
    public function update(Request $request, $id)
    {
        $serviceType = ServiceType::find($id);

        if (!$serviceType) {
            return response()->json([
                'status' => false,
                'message' => 'نوع الخدمة غير موجود',
                'data' => null
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name_en' => 'sometimes|required|string|max:255',
            'name_ar' => 'sometimes|required|string|max:255',
            'category' => 'sometimes|required|string|max:255',
            'icon' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $validator->errors()
            ], 422);
        }

        $serviceType->update($request->all());

        return response()->json([
            'status' => true,
            'message' => 'تم تحديث نوع الخدمة بنجاح',
            'data' => $serviceType
        ]);
    }

    /**
     * حذف نوع خدمة
     */
    public function destroy($id)
    {
        $serviceType = ServiceType::find($id);

        if (!$serviceType) {
            return response()->json([
                'status' => false,
                'message' => 'نوع الخدمة غير موجود',
                'data' => null
            ], 404);
        }

        $serviceType->delete();

        return response()->json([
            'status' => true,
            'message' => 'تم حذف نوع الخدمة بنجاح',
            'data' => null
        ]);
    }

    /**
     * الحصول على أنواع الخدمات حسب الفئة
     */
    public function getByCategory($category)
    {
        $serviceTypes = ServiceType::where('category', $category)
            ->where('is_active', 1)
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'تم استرجاع أنواع الخدمات بنجاح',
            'data' => $serviceTypes
        ]);
    }
}
