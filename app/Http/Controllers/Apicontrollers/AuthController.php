<?php

namespace App\Http\Controllers\Apicontrollers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class AuthController extends Controller
{
    //



    // public function signup(Request $request)
    // {

    //     $request->validate([
    //         "first_name" => "required",
    //         "last_name" => "required",
    //         "email" => "required",
    //         "password" => "required",
    //         "address" => "required",
    //         "phone" => "required"
    //     ]);
    //     if (User::where('email', $request->email)->exists()) {
    //         return response()->json([
    //             "success" => false,
    //             "message" => "E-mail Already Exist!"
    //         ], HttpResponse::HTTP_CONFLICT);
    //     }
    //     User::create($request->all());
    //     return response()->json([
    //         "success" => true,
    //         "message" => "User Created Successfully"
    //     ], 201);
    // }

    public function signup(Request $request)
    {
        $request->validate([
            "first_name" => "required",
            "last_name" => "required",
            "email" => "required|email",
            "password" => "required",
            "address" => "required",
            "phone" => "required"
        ]);

        if (User::where('email', $request->email)->exists()) {
            return response()->json([
                "success" => false,
                "message" => "E-mail Already Exist!"
            ], HttpResponse::HTTP_CONFLICT);
        }

        // إنشاء المستخدم مع تشفير كلمة المرور
        User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => bcrypt($request->password), // تشفير كلمة المرور
            'address' => $request->address,
            'phone' => $request->phone,
        ]);

        return response()->json([
            "success" => true,
            "message" => "User Created Successfully"
        ], 201);
    }


    public function signin(Request $request)
    {
        $request->validate([
            'login' => 'required', // يمكن أن يكون email أو phone
            'password' => 'required',
        ]);

        // تحديد إذا كانت المدخلة email أو phone
        $fieldType = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $credentials = [
            $fieldType => $request->login,
            'password' => $request->password,
        ];

        // تحقق من بيانات الاعتماد
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        // جلب المستخدم بعد تسجيل الدخول
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication failed.',
            ], 401);
        }

        // إنشاء التوكن
        $token = $user->createToken('auth')->plainTextToken;

        return response()->json([
            'success' => true,
            'user' => $user,
            'access_token' => $token,
        ]);
    }
}
