<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Http\Requests\StoreScheduleRequest;
use App\Http\Resources\ScheduleResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    /**
     * Get available time slots for a specific date.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAvailableTimeSlots(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $date = $request->input('date');

        // Here you would check your database for already booked slots
        // and return only available ones

        $timeSlots = [
            ['id' => 'morning', 'start' => '09:00', 'end' => '11:00', 'nameAr' => '9_11 صباحا'],
            ['id' => 'afternoon', 'start' => '12:00', 'end' => '15:00', 'nameAr' => '12_3 بعد الظهر'],
            ['id' => 'evening', 'start' => '15:00', 'end' => '19:00', 'nameAr' => '3_7 مساءا'],
        ];

        return response()->json([
            'date' => $date,
            'availableTimeSlots' => $timeSlots
        ]);
    }

    /**
     * Book a service appointment.
     *
     * @param StoreScheduleRequest $request
     * @return JsonResponse
     */
    public function bookAppointment(StoreScheduleRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $schedule = Schedule::create([
            'date' => $validated['date'],
            'time_slot_id' => $validated['timeSlotId'],
            'is_urgent' => $validated['isUrgent'] ?? false,
            'user_id' => Auth::id() ?? null, // If using authentication
            'status' => 'confirmed',
        ]);

        return response()->json([
            'success' => true,
            'data' => new ScheduleResource($schedule)
        ], 201);
    }
}
