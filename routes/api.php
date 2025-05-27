<?php

use App\Http\Controllers\Apicontrollers\AuthController;
use App\Http\Controllers\SocialiteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\UsersReviewController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\TashtibaController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\ApiControllers\TechnicianAuthController;
use App\Http\Controllers\TechnicianSupportController;
use App\Http\Controllers\TechnicianMotivationController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TechnicianWalletController;


// ✅ Routes غير محمية
Route::post('signup', [AuthController::class, 'signup']);
Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('signin', [AuthController::class, 'signin']);



Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-otp', [AuthController::class, 'resetOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::post('/resend-verify-otp', [AuthController::class, 'resendVerifyOtp']);
Route::post('/resend-reset-otp', [AuthController::class, 'resendResetOtp']);



//Route::get('/auth/google/redirect', [SocialiteController::class, 'redirectToGoogle']);
//Route::get('/auth/google/callback', [SocialiteController::class, 'handleGoogleCallback']);

//Route::get('auth/facebook', [SocialiteController::class, 'redirectToFacebook']);
//Route::get('auth/facebook/callback', [SocialiteController::class, 'handleFacebookCallback']);



Route::post('/reviews', [UsersReviewController::class, 'store']); // API لحفظ الريفيو
Route::get('/reviews', [UsersReviewController::class, 'index']); // API 

Route::post('/support', [SupportController::class, 'sendSupportRequest']);

Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/filter', [ServiceController::class, 'filterServices']);

Route::get('/tashtiba', [TashtibaController::class, 'index']);

Route::get('/offers', [OfferController::class, 'index']);
Route::get('/offers/{id}', [OfferController::class, 'show']);



Route::get('auth/{provider}/redirect', [SocialAuthController::class, 'redirectToProvider']);
Route::get('auth/{provider}/callback', [SocialAuthController::class, 'handleProviderCallback']);



Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});


Route::prefix('technician')->group(function () {
    Route::post('signup', [TechnicianAuthController::class, 'signup']);
    Route::post('verify-otp', [TechnicianAuthController::class, 'verifyOtp']);
    Route::post('signin', [TechnicianAuthController::class, 'signin']);

    Route::post('forgot-password', [TechnicianAuthController::class, 'forgotPassword']);
    Route::post('reset-otp', [TechnicianAuthController::class, 'resetOtp']);
    Route::post('reset-password', [TechnicianAuthController::class, 'resetPassword']);

    Route::post('resend-verify-otp', [TechnicianAuthController::class, 'resendVerifyOtp']);
    Route::post('resend-reset-otp', [TechnicianAuthController::class, 'resendResetOtp']);
    Route::post('experience', [TechnicianAuthController::class, 'updateExperience']);
    Route::post('work-images', [TechnicianAuthController::class, 'uploadWorkImages']);


    // Route to start identity verification process
    Route::post('identity/verify', [TechnicianAuthController::class, 'verifyIdentity']);
});

Route::middleware('auth:sanctum')->group(function () {
    // Route to send a support request
    Route::post('technician/support', [TechnicianSupportController::class, 'sendSupportRequest']);
});

Route::middleware('auth:sanctum')->group(function () {
    // Route to get motivation events (day or week)
    Route::get('technician/motivations', [TechnicianMotivationController::class, 'getMotivationEvents']);

    // Route to participate in a motivation event
    Route::post('technician/motivations/{eventId}/participate', [TechnicianMotivationController::class, 'participateInEvent']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/attendances/{technician_id}', [AttendanceController::class, 'index']);
});

Route::get('/scan/{id}', [AttendanceController::class, 'scanQr'])->name('scan.qr');

Route::get('/technician/qr/{id}', [TechnicianAuthController::class, 'showQr'])->middleware('auth:sanctum');


Route::post('/paymob/payment', [PaymentController::class, 'createPaymobPayment'])->name('paymob.payment');
Route::post('/paymob/callback', [PaymentController::class, 'handlePaymobCallback'])->name('paymob.callback');

Route::prefix('test/technician-wallet')->group(function () {
    Route::post('create', [TechnicianWalletController::class, 'createWallet']);
    Route::post('deposit', [TechnicianWalletController::class, 'deposit']);
    Route::post('withdraw/request', [TechnicianWalletController::class, 'requestWithdrawal']);
    Route::post('withdraw/complete', [TechnicianWalletController::class, 'completeWithdrawal']);
    Route::get('balance', [TechnicianWalletController::class, 'getBalance']);
    Route::get('transactions', [TechnicianWalletController::class, 'getTransactions']);
});