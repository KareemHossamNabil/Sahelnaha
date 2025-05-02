<?php

namespace App\Http\Controllers;

use App\Events\TechnicianOfferEvent;
use App\Models\OrderService;
use App\Models\TechnicianOffer;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Notifications\OfferAcceptedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class UserOfferController extends Controller
{
    /**
     * Get the request object (ServiceRequest or OrderService) based on the offer
     * 
     * @param TechnicianOffer $offer
     * @return array|null
     */
    private function getRequestObjectFromOffer(TechnicianOffer $offer)
    {
        $requestType = $offer->request_type ?? 'service_request';

        if ($requestType === 'service_request' && $offer->service_request_id) {
            $serviceRequest = ServiceRequest::find($offer->service_request_id);
            if ($serviceRequest) {
                return [
                    'object' => $serviceRequest,
                    'type' => 'service_request',
                    'id_field' => 'service_request_id',
                    'id_value' => $offer->service_request_id
                ];
            }
        }

        if ($requestType === 'order_service' && $offer->order_service_id) {
            $orderService = OrderService::find($offer->order_service_id);
            if ($orderService) {
                return [
                    'object' => $orderService,
                    'type' => 'order_service',
                    'id_field' => 'order_service_id',
                    'id_value' => $offer->order_service_id
                ];
            }
        }

        return null;
    }

    /**
     * Check if the authenticated user owns the service request or order
     * 
     * @param mixed $serviceObject
     * @return bool
     */
    private function userOwnsRequest($serviceObject)
    {
        return $serviceObject && $serviceObject->user_id === Auth::id();
    }

    /**
     * Get offers for a specific service request
     * 
     * @param int $serviceRequestId
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Get offers for a specific order service
     * 
     * @param int $orderServiceId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOffersByOrderService($orderServiceId)
    {
        $userId = Auth::id();
        $orderService = OrderService::where('id', $orderServiceId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $offers = TechnicianOffer::where('order_service_id', $orderServiceId)
            ->with(['technician:id,first_name,last_name,experience_text'])
            ->get();

        return response()->json([
            'order_service' => $orderService,
            'offers' => $offers
        ], 200);
    }

    /**
     * Accept an offer
     * 
     * @param int $offerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function acceptOffer($offerId)
    {
        try {
            // Start a database transaction
            DB::beginTransaction();

            $offer = TechnicianOffer::findOrFail($offerId);
            $requestData = $this->getRequestObjectFromOffer($offer);

            if (!$requestData) {
                return response()->json(['message' => 'لم يتم العثور على الطلب المرتبط بهذا العرض'], 404);
            }

            $serviceObject = $requestData['object'];
            $requestType = $requestData['type'];
            $idField = $requestData['id_field'];

            if (!$this->userOwnsRequest($serviceObject)) {
                return response()->json(['message' => 'غير مصرح لك بهذه العملية'], 403);
            }

            if ($serviceObject->status !== 'pending') {
                return response()->json(['message' => 'تم بالفعل اختيار عرض لهذا الطلب'], 400);
            }

            if ($offer->status !== 'pending') {
                return response()->json(['message' => 'لا يمكن قبول هذا العرض في الوقت الحالي'], 400);
            }

            $offer->update([
                'status' => 'in_progress',
                'updated_at' => now(),
            ]);

            $serviceObject->update([
                'status' => 'in_progress',
                'updated_at' => now(),
            ]);

            // Reject all other offers for this request
            TechnicianOffer::where($idField, $serviceObject->id)
                ->where('id', '!=', $offerId)
                ->update([
                    'status' => 'rejected',
                    'updated_at' => now(),
                ]);

            // Get the technician to notify
            $technician = $offer->technician;

            // Notify the technician about the accepted offer
            if ($technician) {
                $technician->notify(new OfferAcceptedNotification($offer, Auth::user(), $serviceObject, $requestType));

                // Dispatch event for real-time updates if needed
                event(new TechnicianOfferEvent('accepted', $offer, $technician, $serviceObject, $requestType));
            }

            // Commit the transaction
            DB::commit();

            return response()->json([
                'message' => 'تم قبول العرض بنجاح',
                'offer' => $offer->load('technician')
            ], 200);
        } catch (\Exception $e) {
            // Rollback the transaction in case of error
            DB::rollBack();

            return response()->json([
                'message' => 'حدث خطأ أثناء معالجة طلبك',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function rejectOffer($offerId)
    {
        $offer = TechnicianOffer::findOrFail($offerId);
        $serviceRequest = ServiceRequest::findOrFail($offer->service_request_id);

        if ($serviceRequest->user_id !== Auth::id()) {
            return response()->json(['message' => 'غير مصرح لك بهذه العملية'], 403);
        }

        if ($offer->status !== 'pending') {
            return response()->json(['message' => 'لا يمكن رفض هذا العرض حال'], 400);
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

    /**
     * Get all offers for the authenticated user
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllOffers()
    {
        $userId = Auth::id();

        // Get all service requests owned by the user
        $serviceRequests = ServiceRequest::where('user_id', $userId)->pluck('id');

        // Get all order services owned by the user
        $orderServices = OrderService::where('user_id', $userId)->pluck('id');

        // Get offers for service requests
        $serviceRequestOffers = TechnicianOffer::whereIn('service_request_id', $serviceRequests)
            ->with(['technician:id,first_name,last_name,experience_text', 'serviceRequest'])
            ->get();

        // Get offers for order services
        $orderServiceOffers = TechnicianOffer::whereIn('order_service_id', $orderServices)
            ->with(['technician:id,first_name,last_name,experience_text', 'orderService'])
            ->get();

        // Combine and format the offers
        $allOffers = $serviceRequestOffers->concat($orderServiceOffers)->map(function ($offer) {
            $requestType = $offer->request_type ?? 'service_request';
            $requestObject = null;

            if ($requestType === 'service_request' && $offer->serviceRequest) {
                $requestObject = $offer->serviceRequest;
            } else if ($requestType === 'order_service' && $offer->orderService) {
                $requestObject = $offer->orderService;
            }

            return [
                'id' => $offer->id,
                'technician' => $offer->technician,
                'description' => $offer->description,
                'min_price' => $offer->min_price,
                'max_price' => $offer->max_price,
                'status' => $offer->status,
                'request_type' => $requestType,
                'request' => $requestObject,
                'created_at' => $offer->created_at,
                'updated_at' => $offer->updated_at,
            ];
        });

        // Group offers by status
        $groupedOffers = $allOffers->groupBy('status');

        // Count offers by status
        $offerCounts = [
            'total' => $allOffers->count(),
            'pending' => $groupedOffers->get('pending', collect())->count(),
            'in_progress' => $groupedOffers->get('in_progress', collect())->count(),
            'completed' => $groupedOffers->get('completed', collect())->count(),
            'rejected' => $groupedOffers->get('rejected', collect())->count(),
            'cancelled' => $groupedOffers->get('cancelled', collect())->count(),
        ];

        return response()->json([
            'offers' => $allOffers,
            'grouped_offers' => $groupedOffers,
            'counts' => $offerCounts
        ], 200);
    }

    /**
     * Get offers by status for the authenticated user
     * 
     * @param string $status
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOffersByStatus($status)
    {
        $userId = Auth::id();

        // Validate status
        $validStatuses = ['pending', 'in_progress', 'completed', 'rejected', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            return response()->json([
                'message' => 'Invalid status. Valid statuses are: ' . implode(', ', $validStatuses)
            ], 400);
        }

        // Get all service requests owned by the user
        $serviceRequests = ServiceRequest::where('user_id', $userId)->pluck('id');

        // Get all order services owned by the user
        $orderServices = OrderService::where('user_id', $userId)->pluck('id');

        // Get offers for service requests with the specified status
        $serviceRequestOffers = TechnicianOffer::whereIn('service_request_id', $serviceRequests)
            ->where('status', $status)
            ->with(['technician:id,first_name,last_name,experience_text', 'serviceRequest'])
            ->get();

        // Get offers for order services with the specified status
        $orderServiceOffers = TechnicianOffer::whereIn('order_service_id', $orderServices)
            ->where('status', $status)
            ->with(['technician:id,first_name,last_name,experience_text', 'orderService'])
            ->get();

        // Combine and format the offers
        $offers = $serviceRequestOffers->concat($orderServiceOffers)->map(function ($offer) {
            $requestType = $offer->request_type ?? 'service_request';
            $requestObject = null;

            if ($requestType === 'service_request' && $offer->serviceRequest) {
                $requestObject = $offer->serviceRequest;
            } else if ($requestType === 'order_service' && $offer->orderService) {
                $requestObject = $offer->orderService;
            }

            return [
                'id' => $offer->id,
                'technician' => $offer->technician,
                'description' => $offer->description,
                'min_price' => $offer->min_price,
                'max_price' => $offer->max_price,
                'status' => $offer->status,
                'request_type' => $requestType,
                'request' => $requestObject,
                'created_at' => $offer->created_at,
                'updated_at' => $offer->updated_at,
            ];
        });

        return response()->json([
            'status' => $status,
            'count' => $offers->count(),
            'offers' => $offers
        ], 200);
    }
}
