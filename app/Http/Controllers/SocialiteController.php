<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Container\Attributes\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;

class SocialiteController extends Controller
{

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function redirectToFacebook()
    {
        return Socialite::driver('facebook')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $user = Socialite::driver('google')->user();
            $finduser = User::where('social_id', $user->id)->first();

            if ($finduser) {
                FacadesAuth::login($finduser);
                return response()->json([
                    'success' => true,
                    'user' => $finduser,
                ]);
            } else {
                // تقسيم الاسم الكامل إلى جزأين
                $nameParts = explode(' ', $user->name, 2); // الحد الأقصى لجزئين
                $firstName = $nameParts[0];
                $lastName = $nameParts[1] ?? ''; // إذا لم يوجد اسم أخير

                $newuser = User::create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $user->email,
                    'social_id' => $user->id,
                    'social_type' => 'google',
                    'password' => Hash::make('my-google'),
                    // إضافة الحقول الإضافية مع قيم افتراضية
                    'address' => '',
                    'phone' => '',
                    'register_otp' => null,
                    'reset_otp' => null,
                    'is_verified' => 1 // تم التحقق عبر السوشيال
                ]);

                FacadesAuth::login($newuser);
                return response()->json([
                    'success' => true,
                    'user' => $newuser,
                ]);
            }
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function handleFacebookCallback()
    {
        try {
            $user = Socialite::driver('facebook')->user();
            $finduser = User::where('social_id', $user->id)->first();

            if ($finduser) {
                FacadesAuth::login($finduser);
                return response()->json([
                    'success' => true,
                    'user' => $finduser,
                ]);
            } else {
                $nameParts = explode(' ', $user->name, 2);
                $firstName = $nameParts[0];
                $lastName = $nameParts[1] ?? '';

                $newuser = User::create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $user->email,
                    'social_id' => $user->id,
                    'social_type' => 'facebook',
                    'password' => Hash::make('my-facebook'),
                    'address' => '',
                    'phone' => '',
                    'register_otp' => null,
                    'reset_otp' => null,
                    'is_verified' => 1
                ]);

                FacadesAuth::login($newuser);
                return response()->json([
                    'success' => true,
                    'user' => $newuser,
                ]);
            }
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
