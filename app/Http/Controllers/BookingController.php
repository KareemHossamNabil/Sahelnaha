<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingProblem;
use App\Models\BookingSchedule;
use App\Models\TimeSlot;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class BookingController extends Controller
{
    /**
     * Display a listing of the user's bookings.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $bookings = Booking::with(['problem', 'schedule', 'address'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bookings
        ]);
    }

    /**
     * Store problem information for a booking.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeProblem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_id' => 'required|exists:services,id',
            'problem_type_id' => 'required|exists:problem_types,id',
            'description' => 'nullable|string|max:1000',
            'images' => 'nullable|array',
            'images.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verify that the problem type belongs to the selected service
        $problemTypeExists = \App\Models\ProblemType::where('id', $request->problem_type_id)
            ->where('service_id', $request->service_id)
            ->exists();

        if (!$problemTypeExists) {
            return response()->json([
                'success' => false,
                'message' => 'The selected problem type does not belong to the selected service.'
            ], 422);
        }

        // Start a new booking or update existing one in session
        $booking = $this->getOrCreateBookingSession($request);

        // Store problem details
        $problem = BookingProblem::updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'service_id' => $request->service_id,
                'problem_type_id' => $request->problem_type_id,
                'description' => $request->description,
                'images' => $request->images ?? [],
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Problem information saved successfully',
            'data' => [
                'booking_id' => $booking->id,
                'problem' => $problem
            ]
        ]);
    }

    /**
     * Get available dates for booking.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableDates(Request $request)
    {
        // Get next 30 days
        $dates = [];
        $startDate = Carbon::today();

        for ($i = 0; $i < 30; $i++) {
            $date = $startDate->copy()->addDays($i);

            // Skip Fridays if they're not working days in your region
            if ($date->dayOfWeek !== Carbon::FRIDAY) {
                $dates[] = [
                    'date' => $date->format('Y-m-d'),
                    'day' => $date->day,
                    'month' => $date->month,
                    'year' => $date->year,
                    'dayName' => $date->format('D'),
                    'available' => true, // You would check availability in a real app
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $dates
        ]);
    }

    /**
     * Get available time slots for a specific date.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableTimeSlots(Request $request)
    {
        // Get time slots from database
        $timeSlots = TimeSlot::all();

        // In a real app, you would check which slots are already booked
        // and mark them as unavailable

        return response()->json([
            'success' => true,
            'data' => $timeSlots
        ]);
    }

    /**
     * Store schedule information for a booking.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeSchedule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:bookings,id',
            'date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'time_slot_id' => 'required|exists:time_slots,id',
            'is_urgent' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verify booking belongs to user
        $booking = Booking::where('id', $request->booking_id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        // Store schedule details
        $schedule = BookingSchedule::updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'date' => $request->date,
                'time_slot_id' => $request->time_slot_id,
                'is_urgent' => $request->is_urgent ?? false,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Schedule information saved successfully',
            'data' => [
                'booking_id' => $booking->id,
                'schedule' => $schedule
            ]
        ]);
    }

    /**
     * Select an address for a booking.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function selectAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:bookings,id',
            'address_id' => 'required|exists:addresses,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verify booking belongs to user
        $booking = Booking::where('id', $request->booking_id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        // Update booking with selected address
        $booking->address_id = $request->address_id;
        $booking->save();

        return response()->json([
            'success' => true,
            'message' => 'Address selected successfully',
            'data' => [
                'booking_id' => $booking->id,
                'address_id' => $booking->address_id
            ]
        ]);
    }

    /**
     * Process payment for a booking.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:bookings,id',
            'payment_method' => 'required|in:credit_card,cash',
            'card_details' => 'required_if:payment_method,credit_card',
            'card_details.last_four_digits' => 'required_if:payment_method,credit_card',
            'card_details.type' => 'required_if:payment_method,credit_card',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verify booking belongs to user
        $booking = Booking::where('id', $request->booking_id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        // Update booking with payment information
        $booking->payment_method = $request->payment_method;

        if ($request->payment_method === 'credit_card') {
            $booking->card_details = $request->card_details;
        }

        $booking->save();

        return response()->json([
            'success' => true,
            'message' => 'Payment information saved successfully',
            'data' => [
                'booking_id' => $booking->id,
                'payment_method' => $booking->payment_method,
                'card_details' => $booking->card_details
            ]
        ]);
    }

    /**
     * Confirm and finalize a booking.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirmBooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'booking_id' => 'required|exists:bookings,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verify booking belongs to user
        $booking = Booking::with(['problem', 'schedule', 'address'])
            ->where('id', $request->booking_id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        // Verify all required information is present
        if (!$booking->problem || !$booking->schedule || !$booking->address_id || !$booking->payment_method) {
            return response()->json([
                'success' => false,
                'message' => 'Booking is incomplete. Please provide all required information.'
            ], 422);
        }

        // Update booking status
        $booking->status = 'confirmed';
        $booking->save();

        return response()->json([
            'success' => true,
            'message' => 'Booking confirmed successfully',
            'data' => $booking
        ]);
    }

    /**
     * Display the specified booking.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        $booking = Booking::with(['problem.problemType', 'schedule.timeSlot', 'address'])
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $booking
        ]);
    }

    /**
     * Get or create a booking session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Models\Booking
     */
    private function getOrCreateBookingSession(Request $request)
    {
        $bookingId = $request->booking_id;

        if ($bookingId) {
            // Verify booking belongs to user
            $booking = Booking::where('id', $bookingId)
                ->where('user_id', $request->user()->id)
                ->where('status', 'pending')
                ->first();

            if ($booking) {
                return $booking;
            }
        }

        // Create new booking
        return Booking::create([
            'user_id' => $request->user()->id,
            'status' => 'pending',
        ]);
    }
}
