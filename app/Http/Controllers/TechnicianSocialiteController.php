<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Technician;
use Exception;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;


class TechnicianSocialiteController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('guest:technician');
    // }
    public function redirectToGoogle()
    {
        /** @var \Laravel\Socialite\Two\GoogleProvider $driver */
        $redirectUrl = config('services.technician_google.redirect');
        $url = Socialite::driver('google')->redirect()->getTargetUrl();

        // استبدل الـ redirect_uri يدويًا
        $url = str_replace(
            'redirect_uri=' . urlencode('https://sahelnaha.systems/api/technician/auth/google/callback'),
            'redirect_uri=' . urlencode($redirectUrl),
            $url
        );

        return redirect()->away($url);
    }
    public function redirectToFacebook()
    {
        /** @var \Laravel\Socialite\Two\FacebookProvider $driver */
        $redirectUrl = config('services.technician_facebook.redirect');
        $url = Socialite::driver('facebook')->redirect()->getTargetUrl();

        // استبدل الـ redirect_uri يدويًا
        $url = str_replace(
            'redirect_uri=' . urlencode('https://sahelnaha.systems/api/technician/auth/facebook/callback'),
            'redirect_uri=' . urlencode($redirectUrl),
            $url
        );

        return redirect()->away($url);
    }

    public function handleGoogleCallback()
    {
        try {
            $user = Socialite::driver('google')->user();
            return $this->handleSocialiteCallback($user, 'google');
        } catch (\Exception $e) {
            Log::error('Google Auth Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Authentication failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function handleFacebookCallback()
    {
        try {
            $user = Socialite::driver('facebook')->user();
            return $this->handleSocialiteCallback($user, 'facebook');
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function handleSocialiteCallback($socialUser, $provider)
    {
        try {
            // 1. البحث في جدول الفنيين فقط
            $technician = Technician::where('social_id', $socialUser->id)
                ->where('social_type', $provider)
                ->first();

            if ($technician) {
                Auth::guard('technician')->login($technician);
                return response()->json([
                    'success' => true,
                    'technician' => $technician,
                ]);
            }

            // 2. إنشاء فني جديد في جدول الفنيين فقط
            $nameParts = explode(' ', $socialUser->name, 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';

            $email = $provider === 'facebook'
                ? ($socialUser->email ?: 'fb_' . $socialUser->id . '@example.com')
                : $socialUser->email;

            $newTechnician = Technician::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'social_id' => $socialUser->id,
                'social_type' => $provider,
                'password' => Hash::make(uniqid()),
                'is_verified' => true,
                'address' => '',
                'phone' => '',
                'identity_image' => null,
                'is_verified_identity' => false,
                'experience_text' => '',
                'work_images' => json_encode([]),
            ]);

            // 3. إنشاء QR code
            $qrData = route('scan.qr', ['id' => $newTechnician->id]);
            $qrImage = QrCode::format('svg')->size(300)->generate($qrData);
            $qrPath = 'qrcodes/tech_' . $newTechnician->id . '.svg';
            Storage::disk('public')->put($qrPath, $qrImage);
            $newTechnician->qr_code = $qrPath;
            $newTechnician->save();

            Auth::guard('technician')->login($newTechnician);

            return response()->json([
                'success' => true,
                'technician' => $newTechnician,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء المصادقة',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
