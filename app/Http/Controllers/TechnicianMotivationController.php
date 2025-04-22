<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\MotivationEvent; // Assuming there's a model for MotivationEvent
use Illuminate\Container\Attributes\Auth as AttributesAuth;

class TechnicianMotivationController extends Controller
{
    // Method to get the motivation events for a specific period (day/week)
    public function getMotivationEvents(Request $request)
    {
        $request->validate([
            'period' => 'required|in:day,week', // Period can either be day or week
        ]);

        $period = $request->period;

        // Fetch motivation events from database or predefined list based on the period
        $events = MotivationEvent::where('period', $period)->get();

        return response()->json([
            'events' => $events,
            'message' => 'Motivational events fetched successfully.',
        ]);
    }

    // Method to mark participation in an event
    public function participateInEvent(Request $request, $eventId)
    {
        /** @var \App\Models\Technician $technician */
        $technician = auth('technician')->user();

        // Assuming we save participation info in a 'participations' table
        $participation = $technician->participations()->create([
            'event_id' => $eventId,
            'status' => 'participated', // Can also be 'won' or 'not_won'
        ]);

        return response()->json([
            'message' => 'Successfully participated in the event.',
            'event' => $participation,
        ]);
    }
}
