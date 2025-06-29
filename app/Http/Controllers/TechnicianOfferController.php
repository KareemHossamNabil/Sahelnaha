<?php

namespace App\Http\Controllers;

use App\Models\TechnicianOffer;
use App\Models\ServiceRequest;
use App\Models\OrderService;
use App\Models\Technician;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Notifications\NewTechnicianOfferNotification;
use App\Notifications\OfferUpdatedNotification;
use App\Notifications\OfferDeletedNotification;
use App\Events\TechnicianOfferEvent;
use App\Services\FirebaseNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TechnicianOfferController extends Controller
{
    private function getTechnicianId()
    {
        $technician = Auth::user();
        if (!$technician instanceof Technician) {
            return response()->json(['message' => 'أنت لست فنيًا مسجلاً في النظام'], 403);
        }
        return $technician->id;
    }

    private function getRequestObject(Request $request)
    {
        if ($request->has('service_request_id')) {
            $serviceRequest = ServiceRequest::find($request->service_request_id);
            if ($serviceRequest) {
                return [
                    'object' => $serviceRequest,
                    'type' => 'service_request',
                    'id_field' => 'service_request_id',
                    'id_value' => $request->service_request_id
                ];
            }
        }

        if ($request->has('order_service_id')) {
            $orderService = OrderService::find($request->order_service_id);
            if ($orderService) {
                return [
                    'object' => $orderService,
                    'type' => 'order_service',
                    'id_field' => 'order_service_id',
                    'id_value' => $request->order_service_id
                ];
            }
        }

        return null;
    }

    // Post Method : Create a technician offer
    public function store(Request $request)
    {
        try {
            $technicianId = $this->getTechnicianId();
            if (is_a($technicianId, \Illuminate\Http\JsonResponse::class)) return $technicianId;

            $validator = Validator::make($request->all(), [
                'service_request_id' => 'required_without:order_service_id|exists:service_requests,id',
                'order_service_id' => 'required_without:service_request_id|exists:order_services,id',
                'description' => 'required|string|min:10',
                'min_price' => 'required|numeric|min:0',
                'max_price' => 'required|numeric|gt:min_price',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $requestData = $this->getRequestObject($request);
            if (!$requestData) {
                return response()->json(['message' => 'لم يتم العثور على الطلب المحدد'], 404);
            }

            $serviceObject = $requestData['object'];
            $requestType = $requestData['type'];
            $idField = $requestData['id_field'];
            $idValue = $requestData['id_value'];

            if (property_exists($serviceObject, 'status') && $serviceObject->status !== 'pending') {
                return response()->json(['message' => 'هذا الطلب لم يعد يقبل العروض'], 400);
            }

            $existingOffer = TechnicianOffer::where($idField, $idValue)
                ->where('technician_id', $technicianId)
                ->first();

            if ($existingOffer) {
                return response()->json(['message' => 'لقد قمت بالفعل بتقديم عرض لهذا الطلب'], 400);
            }

            $offer = TechnicianOffer::create([
                $idField => $idValue,
                'technician_id' => $technicianId,
                'description' => $request->description,
                'min_price' => $request->min_price,
                'max_price' => $request->max_price,
                'currency' => 'جنيه مصري',
                'status' => 'pending',
                'request_type' => $requestType,
            ]);

            $technician = Technician::select('id', 'first_name', 'last_name')->findOrFail($technicianId);
            $user = User::findOrFail($serviceObject->user_id);

            if ($user->is_notify && $user->fcm_token) {
                $title = 'عرض فني جديد';
                $body = $technician->first_name . ' ' . $technician->last_name . ' قدم عرضًا جديدًا لطلبك';
                $type = 'new_offer';
                $data = [
                    'offer_id' => (string) $offer->id,
                    'service_request_id' => (string) $serviceObject->id,
                    'type' => 'new_offer',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
                ];

                Log::info('Sending notification with data:', [
                    'user_id' => $user->id,
                    'title' => $title,
                    'body' => $body,
                    'type' => $type,
                    'data' => $data
                ]);

                (new FirebaseNotificationService)->sendNotification(
                    $user->fcm_token,
                    $title,
                    $body,
                    $type,
                    $data,
                    $user->id
                );
            }

            return response()->json([
                'status' => 201,
                'message' => 'تم إرسال عرضك المقدم والاشعار إلى المستخدم بنجاح وفي انتظار القبول',
                'data' => $offer,
                'technician' => $technician,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء معالجة طلبك',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Put Method : Update a technician offer
    public function update(Request $request, $id)
    {
        $technicianId = $this->getTechnicianId();
        if (is_a($technicianId, \Illuminate\Http\JsonResponse::class)) return $technicianId;

        $offer = TechnicianOffer::where('id', $id)
            ->where('technician_id', $technicianId)
            ->firstOrFail();

        if ($offer->status !== 'pending') {
            return response()->json(['message' => 'لا يمكن تحديث هذا العرض الآن'], 400);
        }

        $validator = Validator::make($request->all(), [
            'description' => 'sometimes|required|string|min:10',
            'min_price' => 'sometimes|required|numeric|min:0',
            'max_price' => 'sometimes|required|numeric|gt:min_price',
            'currency' => 'prohibited',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $oldOffer = clone $offer;

        $offer->update($request->only([
            'description',
            'min_price',
            'max_price',
        ]));

        $offer->currency = 'جنيه مصري';
        $offer->save();

        $technician = Technician::select('id', 'first_name', 'last_name')->findOrFail($technicianId);

        $requestType = $offer->request_type ?? 'service_request';
        $idField = $requestType === 'service_request' ? 'service_request_id' : 'order_service_id';
        $serviceObject = $requestType === 'service_request'
            ? ServiceRequest::findOrFail($offer->$idField)
            : OrderService::findOrFail($offer->$idField);

        $user = User::findOrFail($serviceObject->user_id);

        $user->notify(new OfferUpdatedNotification($offer, $oldOffer, $technician, $serviceObject, $requestType));
        event(new TechnicianOfferEvent('updated', $offer, $technician, $serviceObject, $requestType));

        return response()->json([
            'message' => 'تم تحديث العرض بنجاح',
            'data' => $offer->fresh(),
            'technician' => $technician,
        ], 200);
    }

    // Delete Method : Delete a technician offer
    public function destroy($id)
    {
        $technicianId = $this->getTechnicianId();
        if (is_a($technicianId, \Illuminate\Http\JsonResponse::class)) return $technicianId;

        try {
            $offer = TechnicianOffer::where('id', $id)
                ->where('technician_id', $technicianId)
                ->firstOrFail();

            if ($offer->status !== 'pending') {
                return response()->json(['success' => false, 'message' => 'لا يمكن حذف هذا العرض الآن'], 400);
            }

            $offer->status = 'canceled';
            $offer->save();

            $requestType = $offer->request_type ?? 'service_request';
            $idField = $requestType === 'service_request' ? 'service_request_id' : 'order_service_id';

            if ($requestType === 'service_request') {
                $serviceObject = ServiceRequest::findOrFail($offer->$idField);
            } else {
                $serviceObject = OrderService::findOrFail($offer->$idField);
            }

            $technician = Technician::select('id', 'first_name', 'last_name')->findOrFail($technicianId);
            $user = User::findOrFail($serviceObject->user_id);
            $offerData = clone $offer;

            $user->notify(new OfferDeletedNotification($offerData, $technician, $serviceObject, $requestType));
            event(new TechnicianOfferEvent('canceled', $offerData, $technician, $serviceObject, $requestType));

            // حذف الإشعارات المرتبطة بالعرض
            DatabaseNotification::where('type', 'App\Notifications\NewTechnicianOfferNotification')
                ->orWhere('type', 'App\Notifications\OfferUpdatedNotification')
                ->whereJsonContains('data->offer->id', $offer->id)
                ->delete();

            return response()->json(['success' => true, 'message' => 'تم إلغاء العرض بنجاح'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'العرض غير موجود أو ليس لديك صلاحية لحذفه'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء معالجة طلبك', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all offers for the authenticated technician
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMyOffers()
    {
        $technicianId = $this->getTechnicianId();
        if (is_a($technicianId, \Illuminate\Http\JsonResponse::class)) return $technicianId;

        $offers = TechnicianOffer::where('technician_id', $technicianId)
            ->with(['serviceRequest.user', 'orderService.user'])
            ->get()
            ->map(function ($offer) {
                $requestType = $offer->request_type ?? 'service_request';
                $relatedRequest = $requestType === 'service_request'
                    ? $offer->serviceRequest
                    : $offer->orderService;

                return [
                    'offer' => $offer,
                    'request_type' => $requestType,
                    'request_details' => $relatedRequest,
                ];
            });

        return response()->json([
            'status' => true,
            'offers' => $offers
        ]);
    }

    /**
     * Get offers by specific status for authenticated technician
     *
     * @param Request $request
     * @param string $status
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOffersByStatus(Request $request, $status)
    {
        Log::info('getOffersByStatus called', ['status' => $status]);

        $technicianId = $this->getTechnicianId();
        if (is_a($technicianId, \Illuminate\Http\JsonResponse::class)) return $technicianId;

        $validator = Validator::make(['status' => $status], [
            'status' => 'required|in:pending,accepted,rejected,completed,all',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $offersQuery = TechnicianOffer::with(['serviceRequest.user', 'orderService.user'])
                ->where('technician_id', $technicianId);

            if ($status !== 'all') {
                $offersQuery->where('status', $status);
            }

            $offers = $offersQuery->get()
                ->map(function ($offer) {
                    $requestType = $offer->request_type;
                    $relatedRequest = $requestType === 'service_request'
                        ? $offer->serviceRequest
                        : $offer->orderService;

                    return [
                        'id' => $offer->id,
                        'description' => $offer->description,
                        'min_price' => $offer->min_price,
                        'max_price' => $offer->max_price,
                        'currency' => $offer->currency,
                        'status' => $offer->status,
                        'created_at' => $offer->created_at,
                        'updated_at' => $offer->updated_at,
                        'request_type' => $requestType,
                        'request_details' => $relatedRequest,
                    ];
                });

            return response()->json([
                'status' => 200,
                'offers' => $offers
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching technician offers: ' . $e->getMessage());
            return response()->json([
                'status' => 404,
                'message' => 'حدث خطأ أثناء جلب العروض'
            ], 500);
        }
    }

    /**
     * Cancel an offer by the technician
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelOffer($id)
    {
        try {
            $technician = Auth::user();

            // Find the offer and ensure it belongs to the authenticated technician
            $offer = TechnicianOffer::where('id', $id)
                ->where('technician_id', $technician->id)
                ->firstOrFail();

            // Check if the offer can be cancelled (only pending or accepted offers can be cancelled)
            if (!in_array($offer->status, ['pending', 'accepted'])) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'لا يمكن إلغاء هذا العرض - الحالة الحالية: ' . $offer->status
                ], 400);
            }

            // Start transaction
            DB::beginTransaction();

            try {
                // Update offer status
                $offer->update(['status' => 'cancelled']);

                // If the offer was accepted, update the related request status
                if ($offer->status === 'accepted') {
                    if ($offer->service_request_id) {
                        $offer->serviceRequest->update(['status' => 'pending']);
                    } else if ($offer->order_service_id) {
                        $offer->orderService->update(['status' => 'pending']);
                    }
                }

                // Send notification to the user
                $this->notifyUserAboutOfferCancellation($offer);

                DB::commit();

                return response()->json([
                    'status' => 'success',
                    'message' => 'تم إلغاء العرض بنجاح',
                    'data' => [
                        'offer' => $offer
                    ]
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'العرض غير موجود'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error cancelling offer: ' . $e->getMessage(), [
                'offer_id' => $id,
                'technician_id' => Auth::id()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء إلغاء العرض'
            ], 500);
        }
    }

    /**
     * Notify user about offer cancellation
     *
     * @param TechnicianOffer $offer
     * @return void
     */
    private function notifyUserAboutOfferCancellation($offer)
    {
        try {
            $user = $offer->service_request_id
                ? $offer->serviceRequest->user
                : $offer->orderService->user;

            if ($user && $user->is_notify && $user->fcm_token) {
                $technician = Technician::select('id', 'first_name', 'last_name')->findOrFail($offer->technician_id);
                $title = 'تم إلغاء العرض';
                $body = $technician->first_name . ' ' . $technician->last_name . ' قام بإلغاء العرض الخاص بك';
                $type = 'offer_cancelled';
                $data = [
                    'offer_id' => (string) $offer->id,
                    'technician_id' => (string) $offer->technician_id,
                    'request_type' => $offer->service_request_id ? 'service_request' : 'order_service',
                    'request_id' => (string) ($offer->service_request_id ?? $offer->order_service_id),
                    'type' => 'offer_cancelled',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
                ];

                // Store notification in database
                $user->notifications()->create([
                    'title' => $title,
                    'body' => $body,
                    'type' => $type,
                    'data' => $data
                ]);

                Log::info('Sending cancellation notification with data:', [
                    'user_id' => $user->id,
                    'title' => $title,
                    'body' => $body,
                    'type' => $type,
                    'data' => $data
                ]);

                (new FirebaseNotificationService)->sendNotification(
                    $user->fcm_token,
                    $title,
                    $body,
                    $type,
                    $data,
                    $user->id
                );
            }
        } catch (\Exception $e) {
            Log::error('Error sending cancellation notification: ' . $e->getMessage(), [
                'offer_id' => $offer->id,
                'user_id' => $user->id ?? null
            ]);
        }
    }

    private function notifyTechnicianAboutOfferStatus($offer, $status)
    {
        try {
            $technician = Technician::findOrFail($offer->technician_id);
            $user = $offer->service_request_id
                ? $offer->serviceRequest->user
                : $offer->orderService->user;

            if ($technician && $technician->is_notify && $technician->fcm_token) {
                $title = '';
                $body = '';
                $type = '';

                switch ($status) {
                    case 'accepted':
                        $title = 'تم قبول عرضك';
                        $body = $user->first_name . ' ' . $user->last_name . ' قام بقبول عرضك';
                        $type = 'offer_accepted';
                        break;
                    case 'rejected':
                        $title = 'تم رفض عرضك';
                        $body = $user->first_name . ' ' . $user->last_name . ' قام برفض عرضك';
                        $type = 'offer_rejected';
                        break;
                    case 'cancelled':
                        $title = 'تم إلغاء العرض';
                        $body = $user->first_name . ' ' . $user->last_name . ' قام بإلغاء العرض';
                        $type = 'offer_cancelled';
                        break;
                }

                $data = [
                    'offer_id' => (string) $offer->id,
                    'user_id' => (string) $user->id,
                    'request_type' => $offer->service_request_id ? 'service_request' : 'order_service',
                    'request_id' => (string) ($offer->service_request_id ?? $offer->order_service_id),
                    'type' => $type,
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
                ];

                // Store notification in database
                $technician->notifications()->create([
                    'title' => $title,
                    'body' => $body,
                    'type' => $type,
                    'data' => $data
                ]);

                Log::info('Sending offer status notification to technician:', [
                    'technician_id' => $technician->id,
                    'title' => $title,
                    'body' => $body,
                    'type' => $type,
                    'data' => $data
                ]);

                (new FirebaseNotificationService)->sendNotification(
                    $technician->fcm_token,
                    $title,
                    $body,
                    $type,
                    $data,
                    $technician->id
                );
            }
        } catch (\Exception $e) {
            Log::error('Error sending offer status notification to technician: ' . $e->getMessage(), [
                'offer_id' => $offer->id,
                'technician_id' => $technician->id ?? null
            ]);
        }
    }

    private function notifyUserAboutOfferStatus($offer, $status)
    {
        try {
            $user = $offer->service_request_id
                ? $offer->serviceRequest->user
                : $offer->orderService->user;

            if ($user && $user->is_notify && $user->fcm_token) {
                $technician = Technician::select('id', 'first_name', 'last_name')->findOrFail($offer->technician_id);
                $title = '';
                $body = '';
                $type = '';

                switch ($status) {
                    case 'accepted':
                        $title = 'تم قبول عرضك';
                        $body = 'تم قبول عرضك من قبل ' . $technician->first_name . ' ' . $technician->last_name;
                        $type = 'offer_accepted';
                        break;
                    case 'rejected':
                        $title = 'تم رفض عرضك';
                        $body = 'تم رفض عرضك من قبل ' . $technician->first_name . ' ' . $technician->last_name;
                        $type = 'offer_rejected';
                        break;
                    case 'cancelled':
                        $title = 'تم إلغاء العرض';
                        $body = $technician->first_name . ' ' . $technician->last_name . ' قام بإلغاء العرض';
                        $type = 'offer_cancelled';
                        break;
                }

                $data = [
                    'offer_id' => (string) $offer->id,
                    'technician_id' => (string) $offer->technician_id,
                    'request_type' => $offer->service_request_id ? 'service_request' : 'order_service',
                    'request_id' => (string) ($offer->service_request_id ?? $offer->order_service_id),
                    'type' => $type,
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
                ];

                // Store notification in database
                $user->notifications()->create([
                    'title' => $title,
                    'body' => $body,
                    'type' => $type,
                    'data' => $data
                ]);

                Log::info('Sending offer status notification to user:', [
                    'user_id' => $user->id,
                    'title' => $title,
                    'body' => $body,
                    'type' => $type,
                    'data' => $data
                ]);

                (new FirebaseNotificationService)->sendNotification(
                    $user->fcm_token,
                    $title,
                    $body,
                    $type,
                    $data,
                    $user->id
                );
            }
        } catch (\Exception $e) {
            Log::error('Error sending offer status notification to user: ' . $e->getMessage(), [
                'offer_id' => $offer->id,
                'user_id' => $user->id ?? null
            ]);
        }
    }

    public function updateOfferStatus(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:accepted,rejected,cancelled'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $offer = TechnicianOffer::findOrFail($id);
            $oldStatus = $offer->status;
            $newStatus = $request->status;

            // Update offer status
            $offer->status = $newStatus;
            $offer->save();

            // Send notifications
            if ($oldStatus !== $newStatus) {
                $this->notifyTechnicianAboutOfferStatus($offer, $newStatus);
                $this->notifyUserAboutOfferStatus($offer, $newStatus);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'تم تحديث حالة العرض بنجاح',
                'data' => $offer
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'العرض غير موجود'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error updating offer status: ' . $e->getMessage(), [
                'offer_id' => $id,
                'status' => $request->status
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء تحديث حالة العرض'
            ], 500);
        }
    }

    /**
     * Get location details for an accepted offer
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOfferLocation($id)
    {
        try {
            $technician = Auth::user();

            // Find the offer and ensure it belongs to the authenticated technician
            $offer = TechnicianOffer::where('id', $id)
                ->where('technician_id', $technician->id)
                ->where('status', 'accepted')
                ->firstOrFail();

            // Get the related request (service request or order service)
            $requestType = $offer->request_type;
            $relatedRequest = $requestType === 'service_request'
                ? $offer->serviceRequest
                : $offer->orderService;

            if (!$relatedRequest) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'لم يتم العثور على تفاصيل الطلب'
                ], 404);
            }

            // Get location details
            $locationDetails = [
                'longitude' => $relatedRequest->longitude,
                'latitude' => $relatedRequest->latitude,
                'location' => $relatedRequest->location,
                'request_type' => $requestType,
                'request_id' => $relatedRequest->id,
                'user' => [
                    'id' => $relatedRequest->user->id,
                    'name' => $relatedRequest->user->name,
                    'phone' => $relatedRequest->user->phone
                ]
            ];

            return response()->json([
                'status' => 'success',
                'data' => $locationDetails
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'العرض غير موجود أو لم يتم قبوله'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error retrieving offer location: ' . $e->getMessage(), [
                'offer_id' => $id,
                'technician_id' => Auth::id()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء جلب تفاصيل الموقع'
            ], 500);
        }
    }
}
