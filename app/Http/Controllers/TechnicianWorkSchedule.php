<?php

namespace App\Http\Controllers;

use App\Models\TechnicianWorkSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TechnicianWorkScheduleController extends Controller
{
    // Get logged-in technician's work schedule
    public function index()
    {
        $technicianId = Auth::id();

        $schedules = TechnicianWorkSchedule::where('technician_id', $technicianId)
            ->with(['serviceRequest:id,location,job_type,status', 'serviceRequest.user:id,name'])
            ->orderBy('start_time', 'asc')
            ->get();

        return response()->json([
            'work_schedules' => $schedules
        ]);
    }

    // Optional: Show specific work schedule
    public function show($id)
    {
        $schedule = TechnicianWorkSchedule::with(['serviceRequest', 'technician'])->findOrFail($id);

        return response()->json([
            'schedule' => $schedule
        ]);
    }
}
