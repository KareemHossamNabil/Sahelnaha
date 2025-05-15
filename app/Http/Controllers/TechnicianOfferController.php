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

        // التعديل الرئيسي هنا ليتناسب مع NewTechnicianOfferNotification
        $user->notify(new NewTechnicianOfferNotification(
            $offer->id,                     // offerId
            $serviceObject->id,             // serviceRequestId
            $request->description,          // description
            $technician->id,                // technicianId
            $technician->full_name,         // technicianName
            $request->min_price,            // minPrice
            $request->max_price             // maxPrice
        ));

        event(new TechnicianOfferEvent('created', $offer, $technician, $serviceObject, $requestType));

        return response()->json([
            'status' => 201,
            'message' => 'تم إرسال عرضك المقدم إلى المستخدم بنجاح وفي انتظار القبول',
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
}