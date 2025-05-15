<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ServiceRequest;
use App\Models\OrderService;
use App\Models\TechnicianOffer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Notifications\OfferAcceptedNotification;
use App\Notifications\OfferRejectedNotification;
use App\Exceptions\OfferException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Notifications\NotificationException;

class UserOfferController extends Controller
{
    /**
     * @var TechnicianOffer
     */
    protected $offer;

    /**
     * @var User
     */
    protected $user;

    /**
     * Get all offers for the authenticated user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    function getUserOffers(Request $request)
    {
        Log::info('getUserOffers called', ['request' => $request->all()]);

        $user = Auth::user();
        if (!$user) {
            Log::error('User not authenticated');
            return response()->json(['message' => 'يجب أن تكون مسجلاً كمستخدم لرؤية العروض'], 403);
        }

        Log::info('User authenticated', ['user_id' => $user->id]);

        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|in:service_request,order_service,all',
            'status' => 'sometimes|in:pending,accepted,rejected,all',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $type = $request->input('type', 'all');
        $status = $request->input('status', 'pending');

        try {
            // Get user's requests
            $serviceRequests = ServiceRequest::where('user_id', $user->id)->pluck('id');
            $orderServices = OrderService::where('user_id', $user->id)->pluck('id');

            // Query for offers
            $offersQuery = TechnicianOffer::with(['technician', 'serviceRequest', 'orderService'])
                ->where(function ($query) use ($serviceRequests, $orderServices) {
                    $query->whereIn('service_request_id', $serviceRequests)
                        ->orWhereIn('order_service_id', $orderServices);
                });

            // Filter by type if requested
            if ($type !== 'all') {
                $offersQuery->where('request_type', $type);
            }

            // Filter by status if requested
            if ($status !== 'all') {
                $offersQuery->where('status', $status);
            }

            $offers = $offersQuery->get()
                ->map(function ($offer) {
                    $requestType = $offer->request_type;
                    $requestDetails = $requestType === 'service_request'
                        ? $offer->serviceRequest
                        : $offer->orderService;

                    return [
                        'id' => $offer->id,
                        'technician' => $offer->technician,
                        'description' => $offer->description,
                        'min_price' => $offer->min_price,
                        'max_price' => $offer->max_price,
                        'currency' => $offer->currency,
                        'status' => $offer->status,
                        'created_at' => $offer->created_at,
                        'updated_at' => $offer->updated_at,
                        'request_type' => $requestType,
                        'request_details' => $requestDetails,
                    ];
                });

            return response()->json([
                'status' => true,
                'offers' => $offers
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching user offers: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء جلب العروض'
            ], 500);
        }
    }

    /**
     * Accept an offer
     * 
     * @param int $offerId
     * @return \Illuminate\Http\JsonResponse
     */
    function acceptOffer($offerId)
    {
        DB::beginTransaction();

        try {
            $user = Auth::user();

            // Find offer with relationships
            $offer = TechnicianOffer::with(['serviceRequest', 'orderService'])->findOrFail($offerId);

            // Log the current status for debugging
            Log::info('Offer status check', [
                'offer_id' => $offerId,
                'current_status' => $offer->status
            ]);

            // Verify offer status
            if ($offer->status !== 'pending') {
                throw new OfferException('لا يمكن قبول هذا العرض - الحالة الحالية: ' . $offer->status);
            }

            // Determine request type and verify ownership
            $requestObject = $this->getRequestObject($offer);
            $this->verifyOwnership($requestObject, $user);

            // Update offer and request status
            $offer->update(['status' => 'accepted']);
            $requestObject->update(['status' => 'in_progress']);

            // Reject other offers
            $idField = $offer->service_request_id ? 'service_request_id' : 'order_service_id';
            $this->rejectOtherOffers($idField, $requestObject->id, $offerId);

            // Send notifications
            $this->notifyTechniciansAboutOfferStatus($offer, $idField, $requestObject->id);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'تم قبول العرض بنجاح',
                'data' => [
                    'offer' => $offer,
                    'technician' => $offer->technician
                ]
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            Log::error('Offer not found: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'العرض غير موجود'
            ], 404);
        } catch (OfferException $e) {
            DB::rollBack();
            Log::warning('Offer acceptance failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error accepting offer: ' . $e->getMessage(), [
                'offer_id' => $offerId,
                'user_id' => $user->id ?? null,
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);

            // Check for specific database errors
            if ($e instanceof \Illuminate\Database\QueryException) {
                Log::error('Database error while accepting offer: ' . $e->getMessage(), [
                    'offer_id' => $offerId,
                    'user_id' => $user->id ?? null,
                    'sql' => $e->getSql() ?? 'No SQL available',
                    'bindings' => $e->getBindings() ?? [],
                    'code' => $e->getCode()
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'حدث خطأ في قاعدة البيانات أثناء معالجة الطلب'
                ], 500);
            }
            // Check for notification errors
            // if ($e instanceof \Illuminate\Notifications\NotificationException) {
            //     Log::warning('Notification error while accepting offer: ' . $e->getMessage(), [
            //         'offer_id' => $offerId,
            //         'user_id' => $user->id ?? null,
            //         'exception' => get_class($e),
            //         'trace' => $e->getTraceAsString()
            //     ]);
            //     return response()->json([
            //         'status' => 'error',
            //         'message' => 'تم قبول العرض ولكن حدث خطأ في إرسال الإشعارات'
            //     ], 500);
            // }

            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ غير متوقع أثناء معالجة الطلب'
            ], 500);
        }
    }

    /**
     * Get the request object based on offer type
     * 
     * @param TechnicianOffer $offer
     * @return ServiceRequest|OrderService
     */
    function getRequestObject($offer)
    {
        if ($offer->service_request_id) {
            return ServiceRequest::findOrFail($offer->service_request_id);
        }
        return OrderService::findOrFail($offer->order_service_id);
    }

    /**
     * Verify that the user owns the request
     * 
     * @param mixed $requestObject
     * @param User $user
     * @throws OfferException
     */
    function verifyOwnership($requestObject, $user)
    {
        if (!$requestObject || $requestObject->user_id != $user->id) {
            Log::warning('Unauthorized offer acceptance attempt', [
                'user_id' => $user->id,
                'request_owner' => $requestObject ? $requestObject->user_id : null
            ]);
            throw new OfferException('ليس لديك صلاحية لقبول هذا العرض');
        }
    }

    /**
     * Reject all other offers for the same request
     * 
     * @param string $idField
     * @param int $requestId
     * @param int $acceptedOfferId
     */
    function rejectOtherOffers($idField, $requestId, $acceptedOfferId)
    {
        TechnicianOffer::where($idField, $requestId)
            ->where('id', '!=', $acceptedOfferId)
            ->update(['status' => 'rejected']);
    }

    /**
     * Send notifications to technicians about offer status
     * 
     * @param TechnicianOffer $acceptedOffer
     * @param string $idField
     * @param int $idValue
     */
    function notifyTechniciansAboutOfferStatus($acceptedOffer, $idField, $idValue)
    {
        // Notify accepted technician
        $acceptedTechnician = $acceptedOffer->technician;
        $acceptedTechnician->notify(new OfferAcceptedNotification($acceptedOffer));

        // Notify rejected technicians
        $rejectedOffers = TechnicianOffer::where($idField, $idValue)
            ->where('id', '!=', $acceptedOffer->id)
            ->where('status', 'rejected')
            ->with('technician')
            ->get();

        foreach ($rejectedOffers as $rejectedOffer) {
            $rejectedOffer->technician->notify(new OfferRejectedNotification($rejectedOffer));
        }
    }

    /**
     * Reject an offer
     * 
     * @param int $offerId
     * @return \Illuminate\Http\JsonResponse
     */
    function rejectOffer($offerId)
    {
        try {
            $user = Auth::user();
            $offer = TechnicianOffer::findOrFail($offerId);

            // Verify ownership
            if ($offer->service_request_id) {
                ServiceRequest::where('id', $offer->service_request_id)
                    ->where('user_id', $user->id)
                    ->firstOrFail();
            } else {
                OrderService::where('id', $offer->order_service_id)
                    ->where('user_id', $user->id)
                    ->firstOrFail();
            }

            // Verify offer status
            if ($offer->status !== 'pending') {
                throw new OfferException('لا يمكن رفض هذا العرض - الحالة الحالية: ' . $offer->status);
            }

            $offer->status = 'rejected';
            $offer->save();

            return response()->json([
                'status' => 'success',
                'message' => 'تم رفض العرض بنجاح'
            ]);
        } catch (ModelNotFoundException $e) {
            Log::error('Offer or request not found: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'العرض أو الطلب غير موجود'
            ], 404);
        } catch (OfferException $e) {
            Log::warning('Offer rejection failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error rejecting offer: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء رفض العرض'
            ], 500);
        }
    }

    /**
     * Confirm service completion
     * 
     * @param int $offerId
     * @return \Illuminate\Http\JsonResponse
     */
    function confirmCompletion($offerId)
    {
        try {
            $user = Auth::user();

            $offer = TechnicianOffer::where('id', $offerId)
                ->where('status', 'accepted')
                ->firstOrFail();

            // Verify ownership
            if ($offer->service_request_id) {
                $serviceObject = ServiceRequest::where('id', $offer->service_request_id)
                    ->where('user_id', $user->id)
                    ->firstOrFail();
            } else {
                $serviceObject = OrderService::where('id', $offer->order_service_id)
                    ->where('user_id', $user->id)
                    ->firstOrFail();
            }

            $offer->update(['status' => 'completed']);
            $serviceObject->update(['status' => 'completed']);

            $this->sendFcmToTechnician(
                $offer->technician,
                'تم تأكيد إكمال الخدمة',
                'شكراً لك على خدمتك المميزة'
            );

            return response()->json([
                'status' => 'success',
                'message' => 'تم تأكيد إكمال الخدمة بنجاح'
            ]);
        } catch (ModelNotFoundException $e) {
            Log::error('Offer or request not found: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'العرض أو الطلب غير موجود'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error confirming completion: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء تأكيد إكمال الخدمة'
            ], 500);
        }
    }

    /**
     * Send FCM notification to technician
     * 
     * @param User $technician
     * @param string $title
     * @param string $body
     */
    function sendFcmToTechnician($technician, $title, $body)
    {
        $fcmToken = $technician->fcm_token;
        if (!$fcmToken) return;

        $data = [
            "to" => $fcmToken,
            "notification" => [
                "title" => $title,
                "body" => $body,
                "sound" => "default"
            ],
            "data" => [
                "click_action" => "FLUTTER_NOTIFICATION_CLICK",
                "type" => "offer_update"
            ]
        ];

        $client = new \GuzzleHttp\Client();
        try {
            $client->post('https://fcm.googleapis.com/fcm/send', [
                'headers' => [
                    'Authorization' => 'key=' . env('FCM_SERVER_KEY'),
                    'Content-Type' => 'application/json',
                ],
                'json' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('FCM Error: ' . $e->getMessage());
        }
    }
}
