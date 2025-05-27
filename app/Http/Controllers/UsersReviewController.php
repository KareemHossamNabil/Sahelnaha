<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserReview;
use App\Http\Controllers\Controller;
use App\Models\User;

class UsersReviewController extends Controller
{
    // 1️⃣ دالة لحفظ الريفيو الجديد
    public function store(Request $request)
    {
        // التحقق من صحة البيانات المرسلة
        $request->validate([
            'username' => 'required|string|max:255',
            'review' => 'required|string',
        ]);

        // إضافة الريفيو للداتا بيز
        UserReview::create([
            'username' => $request->username,
            'review' => $request->review,
        ]);

        return response()->json(['message' => 'Review added successfully'], 201);
    }

    // 2️⃣ دالة لجلب كل الريفيوهات
    public function index()
    {
        $reviews = UserReview::all();
        return response()->json($reviews);
    }
}
