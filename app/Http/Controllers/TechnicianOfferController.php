<?php

namespace App\Http\Controllers;

use App\Models\TechnicianOffers;
use App\Models\UserIssues;
use App\Models\Technician;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TechnicianOfferController extends Controller
{
    /**
     * الحصول على جميع العروض المقدمة
     */
    public function index()
    {
        $offers = TechnicianOffers::with(['technician', 'issue'])->get();
        return response()->json(['data' => $offers], 200);
    }

    /**
     * إنشاء عرض جديد
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'issue_id' => 'required|exists:user_issues,id',
            'technician_id' => 'required|exists:technicians,id',
            'description' => 'required|string|min:10',
            'min_price' => 'required|numeric|min:0',
            'max_price' => 'required|numeric|gt:min_price',
            'currency' => 'sometimes|string|in:EGP,USD,EUR',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // التحقق من حالة القضية
        $issue = UserIssues::findOrFail($request->issue_id);
        if ($issue->status !== 'pending') {
            return response()->json(['message' => 'This issue is no longer accepting offers'], 400);
        }

        // التحقق من عدم وجود عرض سابق
        $existingOffer = TechnicianOffers::where('issue_id', $request->issue_id)
            ->where('technician_id', $request->technician_id)
            ->first();

        if ($existingOffer) {
            return response()->json(['message' => 'You have already submitted an offer for this issue'], 400);
        }

        // إنشاء العرض
        $offer = TechnicianOffers::create([
            'issue_id' => $request->issue_id,
            'technician_id' => $request->technician_id,
            'description' => $request->description,
            'min_price' => $request->min_price,
            'max_price' => $request->max_price,
            'currency' => $request->currency ?? 'EGP',
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Offer submitted successfully', 'data' => $offer], 201);
    }

    /**
     * عرض عرض محدد
     */
    public function show($id)
    {
        $offer = TechnicianOffers::with(['technician', 'issue'])->findOrFail($id);
        return response()->json(['data' => $offer], 200);
    }

    /**
     * تحديث عرض موجود
     */
    public function update(Request $request, $id)
    {
        $offer = TechnicianOffers::findOrFail($id);

        // التحقق من حالة العرض
        if ($offer->status !== 'pending') {
            return response()->json(['message' => 'This offer can no longer be updated'], 400);
        }

        $validator = Validator::make($request->all(), [
            'description' => 'sometimes|required|string|min:10',
            'min_price' => 'sometimes|required|numeric|min:0',
            'max_price' => 'sometimes|required|numeric|gt:min_price',
            'status' => 'sometimes|required|in:pending,accepted,rejected',
            'currency' => 'sometimes|string|in:EGP,USD,EUR',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // تحديث العرض
        $offer->update($request->only([
            'description',
            'min_price',
            'max_price',
            'status',
            'currency'
        ]));

        // إذا تم قبول العرض، تحديث القضية ورفض العروض الأخرى
        if ($request->has('status') && $request->status === 'accepted') {
            $issue = UserIssues::findOrFail($offer->issue_id);
            $issue->update([
                'status' => 'assigned',
                'assigned_technician_id' => $offer->technician_id,
            ]);

            // رفض كل العروض الأخرى لهذه القضية
            TechnicianOffers::where('issue_id', $offer->issue_id)
                ->where('id', '!=', $offer->id)
                ->update(['status' => 'rejected']);
        }

        return response()->json([
            'data' => $offer->fresh(['technician', 'issue']),
            'message' => 'Offer updated successfully'
        ], 200);
    }

    /**
     * حذف عرض
     */
    public function destroy($id)
    {
        $offer = TechnicianOffers::findOrFail($id);

        // لا يمكن حذف العرض إلا إذا كان بحالة معلق
        if ($offer->status !== 'pending') {
            return response()->json(['message' => 'This offer can no longer be deleted'], 400);
        }

        $offer->delete();

        return response()->json(['message' => 'Offer deleted successfully'], 200);
    }

    /**
     * الحصول على جميع العروض المقدمة من تقني محدد
     */
    public function getTechnicianOffers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'technician_id' => 'required|exists:technicians,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $offers = TechnicianOffers::where('technician_id', $request->technician_id)
            ->with(['issue', 'issue.user'])
            ->get();

        return response()->json(['data' => $offers], 200);
    }

    /**
     * الحصول على جميع العروض المقدمة لقضية محددة
     */
    public function getIssueOffers($issueId)
    {
        $issue = UserIssues::findOrFail($issueId);

        $offers = TechnicianOffers::where('issue_id', $issueId)
            ->with('technician')
            ->get();

        return response()->json([
            'issue' => $issue,
            'offers' => $offers
        ], 200);
    }
}
