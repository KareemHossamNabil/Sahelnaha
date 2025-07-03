<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Rating;
use App\Models\TechnicianOffer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RatingController extends Controller
{
    /**
     * Store a new rating for a completed offer
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'offer_id' => 'required|exists:technician_offers,id',
                'technician_id' => 'required|exists:technicians,id',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'comment' => 'nullable|string|max:500',
                'rating' => 'required|integer|min:1|max:5',
            ]);

            $offer = TechnicianOffer::findOrFail($validated['offer_id']);

            if ($offer->status !== TechnicianOffer::STATUS_COMPLETED) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'يمكن التقييم فقط للعروض المكتملة'
                ], 400);
            }

            // التحقق من وجود تقييم لنفس العرض ونفس المستخدم
            $existingRating = Rating::where('offer_id', $validated['offer_id'])
                ->where('user_id', Auth::id()) // أضفنا شرط المستخدم
                ->first();

            if ($existingRating) {
                // إضافة تفاصيل للمساعدة في التشخيص
                return response()->json([
                    'status' => 'error',
                    'message' => 'تم التقييم مسبقاً لهذا العرض',
                    'details' => [
                        'user_id' => Auth::id(),
                        'offer_id' => $validated['offer_id'],
                        'existing_rating_id' => $existingRating->id
                    ]
                ], 400);
            }


            $imagePath = null;
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imagePath = $image->store('ratings/invoices', 'public');
            }


            $rating = Rating::create([
                'offer_id' => $validated['offer_id'],
                'technician_id' => $validated['technician_id'],
                'user_id' => Auth::id(),
                'rating' => $validated['rating'],
                'comment' => $validated['comment'],
                'invoice_image' => $imagePath,
            ]);

            // Update technician's average rating
            $this->updateTechnicianAverageRating($validated['technician_id']);

            return response()->json([
                'status' => 201,
                'message' => 'تم إضافة التقييم بنجاح',
                'data' => [
                    'rating' => $rating,
                    'image_url' => $imagePath ? Storage::url($imagePath) : null
                ]
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating rating: ' . $e->getMessage(), [
                'offer_id' => $request->offer_id,
                'technician_id' => $request->technician_id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'حدث خطأ أثناء إضافة التقييم'
            ], 500);
        }
    }

    /**
     * Update technician's average rating
     *
     * @param int $technicianId
     * @return void
     */
    private function updateTechnicianAverageRating($technicianId)
    {
        $averageRating = Rating::where('technician_id', $technicianId)
            ->avg('rating');

        \App\Models\Technician::where('id', $technicianId)
            ->update(['average_rating' => round($averageRating, 1)]);
    }
}
