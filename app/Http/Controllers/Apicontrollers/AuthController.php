<?php

namespace App\Http\Controllers\Apicontrollers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    //  تسجيل المستخدم وإرسال OTP للتفعيل
    public function signup(Request $request)
    {
        $request->validate([
            "first_name" => "required",
            "last_name" => "required",
            "email" => "required|email|unique:users",
            "password" => "required|min:8",
            "address" => "required",
            "phone" => "required"
        ]);

        $otp = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => $request->password,
            'address' => $request->address,
            'phone' => $request->phone,
            'register_otp' => $otp,
            'is_verified' => 0,
        ]);

        // حفظ الـ OTP في الكاش مع البريد الإلكتروني
        Cache::put('register_otp_' . $otp, $request->email, now()->addMinutes(10));

        Mail::to($request->email)->send(new OtpMail($otp));

        return response()->json([
            "success" => true,
            "message" => "User created successfully. Please verify OTP."
        ], 201);
    }

    //  تأكيد OTP عند التسجيل
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:4',
        ]);

        $email = Cache::get('register_otp_' . $request->otp);

        if (!$email) {
            return response()->json(["success" => false, "message" => "Invalid OTP."], 400);
        }

        $user = User::where('email', $email)->first();

        if (!$user || $user->register_otp !== $request->otp) {
            return response()->json(["success" => false, "message" => "Invalid OTP."], 400);
        }

        $user->is_verified = 1;
        $user->register_otp = null;
        $user->save();

        Cache::forget('register_otp_' . $request->otp);

        return response()->json(["success" => true, "message" => "Account verified successfully."], 200);
    }


    //  تسجيل الدخول
    public function signin(Request $request)
    {
        $request->validate([
            'login' => 'required', // يمكن أن يكون email أو phone
            'password' => 'required',
        ]);

        $fieldType = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $credentials = [$fieldType => $request->login, 'password' => $request->password];

        if (!Auth::attempt($credentials)) {
            return response()->json(["success" => false, "message" => "Invalid credentials."], 401);
        }

        $user = Auth::user();

        if (!$user->is_verified) {
            return response()->json(["success" => false, "message" => "Please verify your account first."], 403);
        }

        $token = $user->createToken('auth')->plainTextToken;

        return response()->json(["success" => true, "user" => $user, "access_token" => $token]);
    }

    //  إرسال OTP لإعادة تعيين كلمة المرور
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(["success" => false, "message" => "User not found."], 404);
        }

        $otp = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $user->reset_otp = $otp;
        $user->save();

        // تخزين OTP مع البريد في الكاش
        Cache::put('reset_otp_' . $otp, $request->email, now()->addMinutes(10));

        Mail::to($user->email)->send(new OtpMail($otp));

        return response()->json(["success" => true, "message" => "OTP sent successfully."]);
    }

    //  التحقق من OTP قبل إعادة تعيين كلمة المرور
    public function resetOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:4',
        ]);

        $email = Cache::get('reset_otp_' . $request->otp);

        if (!$email) {
            return response()->json(["success" => false, "message" => "Invalid OTP."], 400);
        }

        // حفظ البريد الإلكتروني في الكاش لمدة 10 دقائق بعد التحقق من OTP
        Cache::put('reset_email', $email, now()->addMinutes(10));

        // حذف الـ OTP بعد استخدامه
        Cache::forget('reset_otp_' . $request->otp);

        return response()->json(["success" => true, "message" => "OTP verified. Proceed to reset password."]);
    }



    //  إعادة تعيين كلمة المرور
    public function resetPassword(Request $request)
    {
        $request->validate([
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        // جلب البريد الإلكتروني المخزن في الكاش
        $email = Cache::get('reset_email');

        if (!$email) {
            return response()->json(["success" => false, "message" => "Unauthorized request."], 401);
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json(["success" => false, "message" => "User not found."], 404);
        }

        $user->password = Hash::make($request->new_password);
        $user->reset_otp = null;
        $user->save();

        // حذف البريد الإلكتروني من الكاش بعد نجاح التغيير
        Cache::forget('reset_email');

        return response()->json(["success" => true, "message" => "Password updated successfully."]);
    }
    public function resendVerifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(["success" => false, "message" => "User not found."], 404);
        }

        if ($user->is_verified) {
            return response()->json(["success" => false, "message" => "Account already verified."], 400);
        }

        // توليد OTP جديد
        $otp = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $user->register_otp = $otp;
        $user->save();

        // تخزين الـ OTP في الكاش
        Cache::put('register_otp_' . $otp, $request->email, now()->addMinutes(10));

        // إرسال OTP عبر البريد
        Mail::to($request->email)->send(new OtpMail($otp));

        return response()->json(["success" => true, "message" => "Verification OTP resent successfully."]);
    }
    public function resendResetOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(["success" => false, "message" => "User not found."], 404);
        }

        // توليد OTP جديد
        $otp = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $user->reset_otp = $otp;
        $user->save();

        // تخزين OTP في الكاش
        Cache::put('reset_otp_' . $otp, $request->email, now()->addMinutes(10));

        // إرسال OTP عبر البريد
        Mail::to($request->email)->send(new OtpMail($otp));

        return response()->json(["success" => true, "message" => "Reset password OTP resent successfully."]);
    }


    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $request->user()->update([
            'fcm_token' => $request->fcm_token,
        ]);

        return response()->json(['message' => 'تم تحديث FCM Token بنجاح']);
    }

    public function getNotifications()
    {
        // الحصول على الفني المتصل
        $user = Auth::user();  // إذا كنت تستخدم التوثيق مع الفنيين بشكل صحيح، سيكون الـ Auth يجلب الـ Technician المتصل

        // تحقق إذا كان الفني قد قام بتسجيل الدخول
        if (!$user) {
            return response()->json([
                'status' => 401,
                'message' => 'يرجى تسجيل الدخول أولاً.',
            ], 401);
        }

        // إرجاع الإشعارات الخاصة بالفني
        return response()->json([
            'status' => 200,
            'notifications' => $user->notifications,
        ]);
    }
}
