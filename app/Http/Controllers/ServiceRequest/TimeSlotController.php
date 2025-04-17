<?php

namespace App\Http\Controllers\ServiceRequest;

use App\Http\Controllers\Controller;
use App\Models\TimeSlot;
use Illuminate\Http\Request;

class TimeSlotController extends Controller
{
    /**
     * Get all time slots.
     */
    public function index()
    {
        $timeSlots = TimeSlot::where('is_active', true)->get();

        if ($timeSlots->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No active time slots found.'
            ], 404);
        }

        return response()->json([
            'success' => 200,
            'data' => $timeSlots
        ]);
    }
}
