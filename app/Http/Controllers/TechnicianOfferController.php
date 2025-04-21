<?php

namespace App\Http\Controllers;

use App\Models\TechnicianOffer;
use App\Models\ServiceRequest;
use App\Models\Technician;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TechnicianOfferController extends Controller
{
    public function store(Request $request)
    {

        $technicianId = Auth::user()->technician->id;
        $validator = Validator::make($request->all(), [
            'service_request_id' => 'required|exists:service_requests,id',
            'description' => 'required|string|min:10',
            'min_price' => 'required|numeric|min:0',
            'max_price' => 'required|numeric|gt:min_price',
            'currency' => 'sometimes|string|in:EGP,USD,EUR',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $serviceRequest = ServiceRequest::findOrFail($request->service_request_id);
        if ($serviceRequest->status !== 'pending') {
            return response()->json(['message' => 'هذا الطلب لم يعد يقبل العروض'], 400);
        }

        $existingOffer = TechnicianOffer::where('service_request_id', $request->service_request_id)
            ->where('technician_id', $technicianId)
            ->first();

        if ($existingOffer) {
            return response()->json(['message' => 'لقد قمت بالفعل بتقديم عرض لهذا الطلب'], 400);
        }


        $offer = TechnicianOffer::create([
            'service_request_id' => $request->service_request_id,
            'technician_id' => $technicianId,
            'description' => $request->description,
            'min_price' => $request->min_price,
            'max_price' => $request->max_price,
            'currency' => $request->currency ?? 'EGP',
            'status' => 'pending',
        ]);

        $technician = Technician::select('id', 'name', 'profession', 'rating')->findOrFail($technicianId);

        $responseData = [
            'message' => 'تم تقديم العرض بنجاح',
            'data' => $offer,
            'technician' => [
                'id' => $technician->id,
                'name' => $technician->name,
                'profession' => $technician->profession,
                'rating' => $technician->rating
            ]
        ];

        return response()->json($responseData, 201);
    }


    public function update(Request $request, $id)
    {

        $technicianId = Auth::user()->technician->id;

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
            'currency' => 'sometimes|string|in:EGP,USD,EUR',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }


        $offer->update($request->only([
            'description',
            'min_price',
            'max_price',
            'currency'
        ]));


        $technician = Technician::select('id', 'name', 'profession', 'rating')->findOrFail($technicianId);

        return response()->json([
            'message' => 'تم تحديث العرض بنجاح',
            'data' => $offer->fresh(['serviceRequest']),
            'technician' => [
                'id' => $technician->id,
                'name' => $technician->name,
                'profession' => $technician->profession,
                'rating' => $technician->rating
            ]
        ], 200);
    }


    public function destroy($id)
    {
        $technicianId = Auth::user()->technician->id;

        $offer = TechnicianOffer::where('id', $id)
            ->where('technician_id', $technicianId)
            ->firstOrFail();

        if ($offer->status !== 'pending') {
            return response()->json(['message' => 'لا يمكن حذف هذا العرض الآن'], 400);
        }

        $offer->delete();

        return response()->json(['message' => 'تم حذف العرض بنجاح'], 200);
    }


    public function getMyOffers()
    {
        $technicianId = Auth::user()->technician->id;

        $offers = TechnicianOffer::where('technician_id', $technicianId)
            ->with(['serviceRequest', 'serviceRequest.user'])
            ->get();

        $technician = Technician::select('id', 'name', 'profession', 'rating')->findOrFail($technicianId);

        return response()->json([
            'technician' => [
                'id' => $technician->id,
                'name' => $technician->name,
                'profession' => $technician->profession,
                'rating' => $technician->rating
            ],
            'offers' => $offers
        ], 200);
    }
}
