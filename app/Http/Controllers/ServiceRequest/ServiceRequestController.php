<?php

namespace App\Http\Controllers\ServiceRequest;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ServiceRequestController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_type_id' => 'required|exists:service_types,id',
            'address_id' => 'required|exists:addresses,id',
            'date' => 'required|date|after_or_equal:today',
            'time_slot_id' => 'required|exists:time_slots,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'description' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Handle image uploads if any
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('service_requests', 'public');
                $imagePaths[] = $path;
            }
        }

        // Create service request
        $serviceRequest = ServiceRequest::create([
            'user_id' => auth()->id(),
            'service_type_id' => $request->service_type_id,
            'address_id' => $request->address_id,
            'scheduled_date' => $request->date,
            'time_slot_id' => $request->time_slot_id,
            'payment_method_id' => $request->payment_method_id,
            'description' => $request->description,
            'images' => json_encode($imagePaths),
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Service request created successfully',
            'data' => $serviceRequest
        ], 201);
    }
}
