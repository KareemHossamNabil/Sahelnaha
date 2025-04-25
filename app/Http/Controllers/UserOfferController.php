<?php

namespace App\Http\Controllers;

use App\Models\TechnicianOffer;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class UserOfferController extends Controller
{
    public function getOffersByServiceRequest($serviceRequestId)
    {
        $userId = Auth::id();
        $serviceRequest = ServiceRequest::where('id', $serviceRequestId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $offers = TechnicianOffer::where('service_request_id', $serviceRequestId)
            ->with(['technician:id,first_name,last_name,experience_text'])
            ->get();

        return response()->json([
            'service_request' => $serviceRequest,
            'offers' => $offers
        ], 200);
    }

    public function acceptOffer($offerId)
    {
        $offer = TechnicianOffer::findOrFail($offerId);
        $serviceRequest = ServiceRequest::findOrFail($offer->service_request_id);

        if ($serviceRequest->user_id !== Auth::id()) {
            return response()->json(['message' => 'غير مصرح لك بهذه العملية'], 403);
        }

        if ($serviceRequest->status !== 'pending') {
            return response()->json(['message' => 'تم بالفعل اختيار عرض لهذه الخدمة'], 400);
        }

        if ($offer->status !== 'pending') {
            return response()->json(['message' => 'لا يمكن قبول هذا العرض في الوقت الحالي'], 400);
        }

        $offer->update([
            'status' => 'in_progress',
            'updated_at' => now(),
        ]);

        $serviceRequest->update([
            'status' => 'in_progress',
            'updated_at' => now(),
        ]);

        TechnicianOffer::where('service_request_id', $serviceRequest->id)
            ->where('id', '!=', $offerId)
            ->update([
                'status' => 'rejected',
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => 'تم قبول العرض بنجاح',
            'offer' => $offer->load('technician')
        ], 200);
    }

    public function rejectOffer($offerId)
    {
        $offer = TechnicianOffer::findOrFail($offerId);
        $serviceRequest = ServiceRequest::findOrFail($offer->service_request_id);

        if ($serviceRequest->user_id !== Auth::id()) {
            return response()->json(['message' => 'غير مصرح لك بهذه العملية'], 403);
        }

        if ($offer->status !== 'pending') {
            return response()->json(['message' => 'لا يمكن رفض هذا العرض حالياً'], 400);
        }

        $offer->update([
            'status' => 'rejected',
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'تم رفض العرض بنجاح',
            'offer' => $offer
        ], 200);
    }

    public function cancelAcceptedOffer(Request $request, $offerId)
    {
        $offer = TechnicianOffer::findOrFail($offerId);
        $serviceRequest = ServiceRequest::findOrFail($offer->service_request_id);

        if ($serviceRequest->user_id !== Auth::id()) {
            return response()->json(['message' => 'غير مصرح لك بهذه العملية'], 403);
        }

        if ($offer->status !== 'in_progress') {
            return response()->json(['message' => 'لا يمكن إلغاء هذا العرض في الوقت الحالي'], 400);
        }

        $validator = Validator::make($request->all(), [
            'cancellation_reason' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $offer->update([
            'status' => 'cancelled',
            'cancellation_reason' => $request->cancellation_reason,
            'updated_at' => now(),
        ]);

        $serviceRequest->update([
            'status' => 'cancelled',
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'تم إلغاء العرض بنجاح',
            'offer' => $offer
        ], 200);
    }

    public function confirmOffer(Request $request, $offerId)
    {
        $offer = TechnicianOffer::findOrFail($offerId);
        $serviceRequest = ServiceRequest::findOrFail($offer->service_request_id);

        if ($serviceRequest->user_id !== Auth::id()) {
            return response()->json(['message' => 'غير مصرح لك بهذه العملية'], 403);
        }

        if ($offer->status !== 'in_progress') {
            return response()->json(['message' => 'لا يمكن تأكيد هذا العرض في الوقت الحالي'], 400);
        }

        $validator = Validator::make($request->all(), [
            'invoice_image' => 'required|image|max:2048',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'required|string|min:10',
            'final_price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $invoicePath = $request->file('invoice_image')->store('invoices', 'public');

        $offer->update([
            'status' => 'completed',
            'invoice_image' => $invoicePath,
            'rating' => $request->rating,
            'review' => $request->review,
            'final_price' => $request->final_price,
            'completed_at' => now(),
            'updated_at' => now(),
        ]);

        $serviceRequest->update([
            'status' => 'completed',
            'updated_at' => now(),
        ]);

        $technician = $offer->technician;
        $completedOffers = TechnicianOffer::where('technician_id', $technician->id)
            ->where('status', 'completed')
            ->whereNotNull('rating')
            ->get();

        $avgRating = $completedOffers->avg('rating');
        $technician->rating = $avgRating;
        $technician->save();

        return response()->json([
            'message' => 'تم تأكيد اكتمال الخدمة بنجاح',
            'offer' => $offer->load('technician')
        ], 200);
    }

    public function getMyAcceptedOffers()
    {
        $userId = Auth::id();
        $serviceRequests = ServiceRequest::where('user_id', $userId)->pluck('id');

        $offers = TechnicianOffer::whereIn('service_request_id', $serviceRequests)
            ->where('status', 'in_progress')
            ->with(['technician:id,first_name,last_name,experience_text', 'serviceRequest'])
            ->get();

        return response()->json([
            'accepted_offers' => $offers
        ], 200);
    }

    public function getMyCompletedOffers()
    {
        $userId = Auth::id();
        $serviceRequests = ServiceRequest::where('user_id', $userId)->pluck('id');

        $offers = TechnicianOffer::whereIn('service_request_id', $serviceRequests)
            ->where('status', 'completed')
            ->with(['technician:id,first_name,last_name,experience_text', 'serviceRequest'])
            ->get();

        return response()->json([
            'completed_offers' => $offers
        ], 200);
    }
}
