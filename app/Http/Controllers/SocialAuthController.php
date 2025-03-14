<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    // توجيه المستخدم إلى Google/Facebook
    public function redirectToProvider($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    // معالجة الرد من Google/Facebook
    public function handleProviderCallback($provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to login, try again.'], 500);
        }

        // البحث عن المستخدم أو إنشاؤه
        $user = User::where('social_id', $socialUser->getId())
            ->where('social_type', $provider)
            ->first();

        if (!$user) {
            $user = User::create([
                'first_name' => explode(' ', $socialUser->getName())[0] ?? '',
                'last_name' => explode(' ', $socialUser->getName())[1] ?? '',
                'email' => $socialUser->getEmail(),
                'social_id' => $socialUser->getId(),
                'social_type' => $provider,
                'is_verified' => true,
            ]);
        }

        // إنشاء توكن Sanctum
        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }
}
