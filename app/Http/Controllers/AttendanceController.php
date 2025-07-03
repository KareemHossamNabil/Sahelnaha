<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{


    public function index($technician_id)
    {
        $attendances = Attendance::where('technician_id', $technician_id)
            ->orderByDesc('scanned_at')
            ->get();

        return response()->json($attendances);
    }



    public function scanQr(Request $request, $technicianId)
    {
        // الحصول على المستخدم المسجل دخوله (الذي قام بالمسح)
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'يجب تسجيل الدخول أولاً'
            ], 401);
        }

        $technician = Technician::find($technicianId);

        if (!$technician) {
            return response()->json([
                'message' => 'الفني غير موجود'
            ], 404);
        }

        // تسجيل الحضور مع هوية المستخدم
        Attendance::create([
            'technician_id' => $technicianId,
            'user_id' => $user->id,
            'scanned_at' => now()
        ]);

        return response()->json([
            'message' => 'تم تسجيل الحضور بنجاح',
            'technician' => $technician,
            'scanned_by' => $user->name
        ]);
    }
}
