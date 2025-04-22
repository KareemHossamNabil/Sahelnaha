<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Technician;

class AttendanceController extends Controller
{


    public function index($technician_id)
    {
        $attendances = Attendance::where('technician_id', $technician_id)
            ->orderByDesc('scanned_at')
            ->get();

        return response()->json($attendances);
    }

    public function scanQr($id, Request $request)
    {
        $technician = Technician::find($id);
        if (!$technician) {
            return response()->json(['message' => 'Technician not found'], 404);
        }

        // هنا انت مش محتاج latitude و longitude زي ما قلت
        // تسجيل الحضور للفني مباشرة بدون latitude و longitude
        $attendance = Attendance::create([
            'technician_id' => $technician->id,
            'scanned_at' => now(),
        ]);

        return response()->json(['message' => 'Attendance recorded', 'data' => $attendance]);
    }
}
