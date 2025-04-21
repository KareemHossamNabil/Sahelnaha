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
            ->with(['technician:id,name,profession,rating,profile_image'])
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

        if ($offer->status !== 'pending') {
            return response()->json(['message' => 'لا يمكن قبول هذا العرض في الوقت الحالي'], 400);
        }

        $offer->status = 'in_progress';
        $offer->save();

        $serviceRequest->status = 'in_progress';
        $serviceRequest->save();

        TechnicianOffer::where('service_request_id', $serviceRequest->id)
            ->where('id', '!=', $offerId)
            ->update(['status' => 'rejected']);

        return response()->json([
            'message' => 'تم قبول العرض بنجاح',
            'offer' => $offer->load('technician')
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

        $offer->status = 'cancelled';
        $offer->cancellation_reason = $request->cancellation_reason;
        $offer->save();

        $serviceRequest->status = 'cancelled';
        $serviceRequest->save();

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


        $offer->status = 'completed';
        $offer->invoice_image = $invoicePath;
        $offer->rating = $request->rating;
        $offer->review = $request->review;
        $offer->final_price = $request->final_price;
        $offer->completed_at = now();
        $offer->save();

        $serviceRequest->status = 'completed';
        $serviceRequest->save();

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
            ->with(['technician:id,name,profession,rating,profile_image', 'serviceRequest'])
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
            ->with(['technician:id,name,profession,rating,profile_image', 'serviceRequest'])
            ->get();

        return response()->json([
            'completed_offers' => $offers
        ], 200);
    }
}
