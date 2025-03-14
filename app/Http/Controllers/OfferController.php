<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    // جلب كل العروض
    public function index()
    {
        $offers = Offer::select('id', 'name', 'image_url', 'discount')->get();

        // تعديل الصورة لتكون URL كامل
        $offers = $offers->map(function ($offer) {
            $offer->image_url = url($offer->image_url);
            return $offer;
        });

        return response()->json([
            'status' => 'success',
            'data' => $offers
        ], 200);
    }

    // جلب عرض واحد حسب ID
    public function show($id)
    {
        $offer = Offer::select('id', 'name', 'image_url', 'discount')->find($id);
        if (!$offer) {
            return response()->json(['status' => 'error', 'message' => 'Offer not found'], 404);
        }

        // تعديل الصورة لتكون URL كامل
        $offer->image_url = url($offer->image_url);

        return response()->json([
            'status' => 'success',
            'data' => $offer
        ], 200);
    }
}
