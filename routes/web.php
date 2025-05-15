<?php
use App\Helpers\FcmHelper;

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
// dd(env('GOOGLE_CLIENT_ID'), env('GOOGLE_CLIENT_SECRET'), env('GOOGLE_REDIRECT_URL'));

Route::get('/test-fcm', function () {
    $fcmToken = 'ضع_هنا_توكن_حقيقي_لجهاز';
    $title = 'اختبار إشعار';
    $body = 'هذا إشعار تجريبي';

    $response = FcmHelper::sendNotification($fcmToken, $title, $body);

    return response()->json($response);
});
