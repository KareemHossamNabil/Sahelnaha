<?php

namespace App\Http\Controllers\Apicontrollers;

use App\Http\Controllers\Controller;
use App\Models\Technician;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
// use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use SimpleQRCode;

class TechnicianAuthController extends Controller
{


    public function signup(Request $request)
    {
        try {
            $request->validate([
                "first_name" => "required",
                "last_name" => "required",
                "email" => "required|email|unique:technicians,email",
                "password" => "required|min:8",
                "address" => "required",
                "phone" => "required|unique:technicians,phone"
            ]);
        } catch (ValidationException $e) {
            $errors = $e->errors();

            // تخصيص الرسالة حسب نوع الخطأ
            if (isset($errors['email']) || isset($errors['phone'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'رقم الهاتف أو البريد الإلكتروني مستخدم من قبل.'
                ], 409); // Conflict
            }

            // لو في أخطاء تانية
            return response()->json([
                'success' => false,
                'message' => 'يوجد خطأ في البيانات المدخلة.',
                'errors' => $errors
            ], 422); // Unprocessable Entity
        }

        // باقي الكود كالمعتاد
        $otp = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);

        $technician = Technician::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'address' => $request->address,
            'phone' => $request->phone,
            'register_otp' => $otp,
            'is_verified' => 0,
        ]);

        Cache::put('tech_register_otp_' . $otp, $request->email, now()->addMinutes(10));
        Mail::to($request->email)->send(new OtpMail($otp));

        return response()->json(["success" => true, "message" => "تم التسجيل بنجاح، يرجى تأكيد OTP."]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate(['otp' => 'required|digits:4']);
        $email = Cache::get('tech_register_otp_' . $request->otp);

        if (!$email) return response()->json(["success" => false, "message" => "Invalid OTP."], 400);

        $technician = Technician::where('email', $email)->first();
        if (!$technician || $technician->register_otp !== $request->otp)
            return response()->json(["success" => false, "message" => "Invalid OTP."], 400);

        $technician->is_verified = 1;
        $technician->register_otp = null;

        // ✅ توليد QR Code
        // $qrData = route('scan.qr', ['id' => $technician->id]); // أو استخدم API URL
        // $qrImage = Qrcode::format('svg')->size(300)->generate($qrData);
        // $qrPath = 'qrcodes/tech_' . $technician->id . '.svg';
        // Storage::disk('public')->put($qrPath, $qrImage);
        // $technician->qr_code = $qrPath;

        $technician->save();

        Cache::forget('tech_register_otp_' . $request->otp);

        return response()->json(["success" => true, "message" => "Account verified successfully."]);
    }

    public function signin(Request $request)
    {
        $request->validate([
            'login' => 'required',
            'password' => 'required',
        ]);

        $fieldType = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $technician = Technician::where($fieldType, $request->login)->first();
        if (!$technician || !Hash::check($request->password, $technician->password)) {
            return response()->json(["success" => false, "message" => "Invalid credentials."], 401);
        }

        if (!$technician->is_verified)
            return response()->json(["success" => false, "message" => "Please verify your account first."], 403);

        $token = $technician->createToken('tech_auth')->plainTextToken;

        return response()->json(["success" => true, "technician" => $technician, "access_token" => $token]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $technician = Technician::where('email', $request->email)->first();

        if (!$technician)
            return response()->json(["success" => false, "message" => "Technician not found."], 404);

        $otp = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $technician->reset_otp = $otp;
        $technician->save();

        Cache::put('tech_reset_otp_' . $otp, $request->email, now()->addMinutes(10));
        Mail::to($technician->email)->send(new OtpMail($otp));

        return response()->json(["success" => true, "message" => "OTP sent."]);
    }

    public function resetOtp(Request $request)
    {
        $request->validate(['otp' => 'required|digits:4']);
        $email = Cache::get('tech_reset_otp_' . $request->otp);

        if (!$email)
            return response()->json(["success" => false, "message" => "Invalid OTP."], 400);

        Cache::put('tech_reset_email', $email, now()->addMinutes(10));
        Cache::forget('tech_reset_otp_' . $request->otp);

        return response()->json(["success" => true, "message" => "OTP verified."]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate(['new_password' => 'required|string|min:8|confirmed']);
        $email = Cache::get('tech_reset_email');

        if (!$email)
            return response()->json(["success" => false, "message" => "Unauthorized request."], 401);

        $technician = Technician::where('email', $email)->first();
        if (!$technician)
            return response()->json(["success" => false, "message" => "Technician not found."], 404);

        $technician->password = Hash::make($request->new_password);
        $technician->reset_otp = null;
        $technician->save();

        Cache::forget('tech_reset_email');

        return response()->json(["success" => true, "message" => "Password updated successfully."]);
    }

    public function resendVerifyOtp(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:technicians,email']);
        $technician = Technician::where('email', $request->email)->first();

        if ($technician->is_verified)
            return response()->json(["success" => false, "message" => "Already verified."]);

        $otp = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $technician->register_otp = $otp;
        $technician->save();

        Cache::put('tech_register_otp_' . $otp, $request->email, now()->addMinutes(10));
        Mail::to($request->email)->send(new OtpMail($otp));

        return response()->json(["success" => true, "message" => "Verification OTP resent."]);
    }

    public function resendResetOtp(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:technicians,email']);
        $technician = Technician::where('email', $request->email)->first();

        $otp = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $technician->reset_otp = $otp;
        $technician->save();

        Cache::put('tech_reset_otp_' . $otp, $request->email, now()->addMinutes(10));
        Mail::to($request->email)->send(new OtpMail($otp));

        return response()->json(["success" => true, "message" => "Reset OTP resent."]);
    }
    public function verifyIdentity(Request $request)
    {
        $request->validate([
            'technician_id' => 'required|exists:technicians,id',
            'image' => 'required|image'
        ]);

        $image = $request->file('image');
        $imagePath = $image->store('identity_verifications', 'public');

        // Call Flask API
        $flaskUrl = 'http://127.0.0.1:5000/api/verify-id';
        $response = Http::attach('image', file_get_contents($image), $image->getClientOriginalName())
            ->post($flaskUrl);

        if ($response->successful() && $response['verified']) {
            $technician = Technician::find($request->technician_id);
            $technician->identity_image = $imagePath;
            $technician->is_verified_identity = 1;
            $technician->save();

            return response()->json(["success" => true, "message" => "Identity verified successfully."]);
        }

        return response()->json(["success" => false, "message" => "Identity verification failed."], 422);
    }
    public function updateExperience(Request $request)
    {
        $request->validate([
            'experience_text' => 'required|string|max:1000',
        ]);
        /** @var \App\Models\Technician $technician */
        $technician = auth('technician')->user();
        // Get the authenticated technician

        // Update the technician's experience text
        $technician->experience_text = $request->experience_text;
        $technician->save();

        return response()->json([
            'message' => 'Experience updated successfully.',
            'experience_text' => $technician->experience_text,
        ]);
    }

    // Method to upload work images
    public function uploadWorkImages(Request $request)
    {
        $request->validate([
            'work_images' => 'required|array',
            'work_images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Image validation
        ]);

        $technician = auth('technician')->user();
        // Get the authenticated technician

        $imagePaths = [];

        // Store each image
        foreach ($request->file('work_images') as $image) {
            $imagePath = $image->store('work_images', 'public');
            $imagePaths[] = $imagePath; // Save the path of the uploaded image
        }
        /** @var \App\Models\Technician $technician */
        // Optionally, you can save these image paths to the technician's record if needed
        $technician->work_images = json_encode($imagePaths);
        $technician->save();

        return response()->json([
            'message' => 'Work images uploaded successfully.',
            'work_images' => $imagePaths,
        ]);
    }


    // عرض صورة QR Code
    public function showQr($id)
    {
        $technician = Technician::find($id);
        if (!$technician) {
            return response()->json(['message' => 'Technician not found'], 404);
        }

        // هنا بنولد صورة الـ QR
        // $qrCode = QrCode::size(300)->generate(route('scan.qr', $technician->id));  // بتولد الرابط الخاص بالمسار

        // return response($qrCode)->header('Content-Type', 'image/svg+xml');
    }
}
