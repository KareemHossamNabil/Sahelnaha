<?php

namespace App\Http\Controllers;

use App\Models\TechnicianOffer;
use App\Models\ServiceRequest;
use App\Models\OrderService;
use App\Models\Technician;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Notifications\NewTechnicianOfferNotification;
use App\Notifications\OfferUpdatedNotification;
use App\Notifications\OfferDeletedNotification;
use App\Events\TechnicianOfferEvent;

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

    /**
     * Get the request object (ServiceRequest or OrderService) based on the provided IDs
     */
    private function getRequestObject(Request $request)
    {
        // Check if service_request_id is provided
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

        // Check if order_service_id is provided
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

    public function store(Request $request)
    {
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

        // Get the request object (ServiceRequest or OrderService)
        $requestData = $this->getRequestObject($request);
        if (!$requestData) {
            return response()->json(['message' => 'لم يتم العثور على الطلب المحدد'], 404);
        }

        $serviceObject = $requestData['object'];
        $requestType = $requestData['type'];
        $idField = $requestData['id_field'];
        $idValue = $requestData['id_value'];

        // Check if the request is still pending
        if (property_exists($serviceObject, 'status') && $serviceObject->status !== 'pending') {
            return response()->json(['message' => 'هذا الطلب لم يعد يقبل العروض'], 400);
        }

        // Check if the technician already made an offer for this request
        $existingOffer = TechnicianOffer::where($idField, $idValue)
            ->where('technician_id', $technicianId)
            ->first();

        if ($existingOffer) {
            return response()->json(['message' => 'لقد قمت بالفعل بتقديم عرض لهذا الطلب'], 400);
        }

        // Create the offer
        $offerData = [
            $idField => $idValue,
            'technician_id' => $technicianId,
            'description' => $request->description,
            'min_price' => $request->min_price,
            'max_price' => $request->max_price,
            'currency' => 'جنيه مصري',
            'status' => 'pending',
            'request_type' => $requestType, // Store the type of request
        ];

        $offer = TechnicianOffer::create($offerData);

        $technician = Technician::select('id', 'first_name', 'last_name')->findOrFail($technicianId);

        // Get the user to notify
        $userId = $serviceObject->user_id;
        $user = User::findOrFail($userId);

        // Notify the user about the new offer
        $user->notify(new NewTechnicianOfferNotification($offer, $technician, $serviceObject, $requestType));

        // Dispatch event for real-time updates if needed
        event(new TechnicianOfferEvent('created', $offer, $technician, $serviceObject, $requestType));

        return response()->json([
            'message' => 'تم تقديم العرض بنجاح',
            'data' => $offer,
            'technician' => $technician,
        ], 201);
    }

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
            'currency' => 'prohibited', // العملة ممنوع إرسالها
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Store old values for comparison
        $oldOffer = clone $offer;

        $offer->update($request->only([
            'description',
            'min_price',
            'max_price',
        ]));

        $offer->currency = 'جنيه مصري';
        $offer->save();

        $technician = Technician::select('id', 'first_name', 'last_name')->findOrFail($technicianId);

        // Get the service object based on the request type
        $requestType = $offer->request_type ?? 'service_request';
        $idField = $requestType === 'service_request' ? 'service_request_id' : 'order_service_id';

        if ($requestType === 'service_request') {
            $serviceObject = ServiceRequest::findOrFail($offer->$idField);
        } else {
            $serviceObject = OrderService::findOrFail($offer->$idField);
        }

        $user = User::findOrFail($serviceObject->user_id);

        // Notify the user about the updated offer
        $user->notify(new OfferUpdatedNotification($offer, $oldOffer, $technician, $serviceObject, $requestType));

        // Dispatch event for real-time updates if needed
        event(new TechnicianOfferEvent('updated', $offer, $technician, $serviceObject, $requestType));

        return response()->json([
            'message' => 'تم تحديث العرض بنجاح',
            'data' => $offer->fresh(),
            'technician' => $technician,
        ], 200);
    }

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

            // تغيير الحالة إلى "<|im_start|>لغي" بدلاً من الحذف مباشرة
            $offer->status = 'canceled';
            $offer->save();

            // Get the service object based on the request type
            $requestType = $offer->request_type ?? 'service_request';
            $idField = $requestType === 'service_request' ? 'service_request_id' : 'order_service_id';

            try {
                if ($requestType === 'service_request') {
                    $serviceObject = ServiceRequest::findOrFail($offer->$idField);
                } else {
                    $serviceObject = OrderService::findOrFail($offer->$idField);
                }

                $technician = Technician::select('id', 'first_name', 'last_name')->findOrFail($technicianId);
                $user = User::findOrFail($serviceObject->user_id);

                // Store offer data for notification
                $offerData = clone $offer;

                // Notify the user about the canceled offer
                $user->notify(new OfferDeletedNotification($offerData, $technician, $serviceObject, $requestType));

                // Dispatch event for real-time updates if needed
                event(new TechnicianOfferEvent('canceled', $offerData, $technician, $serviceObject, $requestType));

                return response()->json(['success' => true, 'message' => 'تم إلغاء العرض بنجاح'], 200);
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                // Service object not found but offer was canceled
                return response()->json(['success' => true, 'message' => 'تم إلغاء العرض بنجاح، ولكن لم يتم إرسال الإشعار'], 200);
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'العرض غير موجود أو ليس لديك صلاحية لحذفه'], 404);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء معالجة طلبك', 'error' => $e->getMessage()], 500);
        }
    }


    public function getMyOffers()
    {
        $technicianId = $this->getTechnicianId();
        if (is_a($technicianId, \Illuminate\Http\JsonResponse::class)) return $technicianId;

        $offers = TechnicianOffer::where('technician_id', $technicianId)
            ->get()
            ->map(function ($offer) {
                $requestType = $offer->request_type ?? 'service_request';
                $idField = $requestType === 'service_request' ? 'service_request_id' : 'order_service_id';

                if ($requestType === 'service_request' && $offer->$idField) {
                    $serviceObject = ServiceRequest::with('user')->find($offer->$idField);
                } else if ($requestType === 'order_service' && $offer->$idField) {
                    $serviceObject = OrderService::with('user')->find($offer->$idField);
                } else {
                    $serviceObject = null;
                }

                return [
                    'id' => $offer->id,
                    'description' => $offer->description,
                    'min_price' => $offer->min_price,
                    'max_price' => $offer->max_price,
                    'currency' => $offer->currency,
                    'status' => $offer->status,
                    'request_type' => $requestType,
                    'request' => $serviceObject,
                    'created_at' => $offer->created_at,
                    'updated_at' => $offer->updated_at,
                ];
            });

        $technician = Technician::select('id', 'first_name', 'last_name')->findOrFail($technicianId);

        return response()->json([
            'technician' => $technician,
            'offers' => $offers,
        ], 200);
    }

    /**
     * Get technician offers filtered by status
     * 
     * @param string $status
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOffersByStatus($status)
    {
        $technicianId = $this->getTechnicianId();
        if (is_a($technicianId, \Illuminate\Http\JsonResponse::class)) return $technicianId;

        // Validate status parameter
        $validStatuses = ['pending', 'in_progress', 'completed', 'canceled', 'rejected'];
        if (!in_array($status, $validStatuses)) {
            return response()->json([
                'success' => false,
                'message' => 'حالة غير صالحة',
                'valid_statuses' => $validStatuses
            ], 422);
        }

        try {
            $offers = TechnicianOffer::where('technician_id', $technicianId)
                ->where('status', $status)
                ->get()
                ->map(function ($offer) {
                    $requestType = $offer->request_type ?? 'service_request';
                    $idField = $requestType === 'service_request' ? 'service_request_id' : 'order_service_id';

                    if ($requestType === 'service_request' && $offer->$idField) {
                        $serviceObject = ServiceRequest::with('user')->find($offer->$idField);
                    } else if ($requestType === 'order_service' && $offer->$idField) {
                        $serviceObject = OrderService::with('user')->find($offer->$idField);
                    } else {
                        $serviceObject = null;
                    }

                    return [
                        'id' => $offer->id,
                        'description' => $offer->description,
                        'min_price' => $offer->min_price,
                        'max_price' => $offer->max_price,
                        'currency' => $offer->currency,
                        'status' => $offer->status,
                        'request_type' => $requestType,
                        'request' => $serviceObject,
                        'created_at' => $offer->created_at,
                        'updated_at' => $offer->updated_at,
                    ];
                });

            $technician = Technician::select('id', 'first_name', 'last_name')->findOrFail($technicianId);

            return response()->json([
                'success' => true,
                'technician' => $technician,
                'offers' => $offers,
                'status' => $status,
                'count' => $offers->count()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب العروض',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
