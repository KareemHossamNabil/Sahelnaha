<?php

use App\Http\Controllers\Apicontrollers\AuthController;
use App\Http\Controllers\BookingController;
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
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\TechnicianOfferController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\TechnicianWorkScheduleController;
use App\Http\Controllers\OrderServiceController;
use App\Http\Controllers\UserOfferController;
use App\Http\Controllers\UserNotificationController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TechnicianWalletController;

/*
|--------------------------------------------------------------------------
| Public Routes (No Authentication Required)
|--------------------------------------------------------------------------
*/

// User Authentication
Route::post('signup', [AuthController::class, 'signup']);
Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('signin', [AuthController::class, 'signin']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-otp', [AuthController::class, 'resetOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/resend-verify-otp', [AuthController::class, 'resendVerifyOtp']);
Route::post('/resend-reset-otp', [AuthController::class, 'resendResetOtp']);

// Reviews
Route::post('/reviews', [UsersReviewController::class, 'store']);
Route::get('/reviews', [UsersReviewController::class, 'index']);


Route::post('/reviews', [UsersReviewController::class, 'store']); // API لحفظ الريفيو
Route::get('/reviews', [UsersReviewController::class, 'index']); // API 

// Support
Route::post('/support', [SupportController::class, 'sendSupportRequest']);

// Services
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/services/filter', [ServiceController::class, 'filterServices']);

// Tashtiba
Route::get('/tashtiba', [TashtibaController::class, 'index']);

// Offers
Route::get('/offers', [OfferController::class, 'index']);
Route::get('/offers/{id}', [OfferController::class, 'show']);


// Route::get('auth/google/redirect', [SocialiteController::class, 'redirectToGoogle']);
// Route::get('auth/google/callback', [SocialiteController::class, 'handleGoogleCallback']);
// Route::get('auth/facebook', [SocialiteController::class, 'redirectToFacebook']);
// Route::get('auth/facebook/callback', [SocialiteController::class, 'handleFacebookCallback']);
// Route::get('auth/{provider}/redirect', [SocialAuthController::class, 'redirectToProvider']);
// Route::get('auth/{provider}/callback', [SocialAuthController::class, 'handleProviderCallback']);

// أضف middleware الويب للسوشيال فقط
// Route::middleware('web')->group(function () {
//     Route::get('/auth/google/redirect', [SocialiteController::class, 'redirectToGoogle']);
//     Route::get('/auth/google/callback', [SocialiteController::class, 'handleGoogleCallback']);

//     Route::get('/auth/facebook/redirect', [SocialiteController::class, 'redirectToFacebook']);
//     Route::get('/auth/facebook/callback', [SocialiteController::class, 'handleFacebookCallback']);
// });
// Social Authentication
Route::get('auth/{provider}/redirect', [SocialAuthController::class, 'redirectToProvider']);
Route::get('auth/{provider}/callback', [SocialAuthController::class, 'handleProviderCallback']);

// Products
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/products/category/{category}', [ProductController::class, 'filterByCategory']);

// Service Requests
Route::get('service-requests', [ServiceRequestController::class, 'index']);
// Route::get('service-request/{id}', [ServiceRequestController::class, 'getServiceRequestById']);

/*
|--------------------------------------------------------------------------
| Technician Authentication Routes
|--------------------------------------------------------------------------
*/
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
    Route::post('identity/verify', [TechnicianAuthController::class, 'verifyIdentity']);
});

/*
|--------------------------------------------------------------------------
| Protected Routes (Authentication Required)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // Product Reviews
    Route::post('/products/{id}/reviews', [ProductReviewController::class, 'store']);

    // Notifications
    Route::get('/technician/my-notifications', [TechnicianAuthController::class, 'getNotifications']);
    Route::get('/user/my-notifications', [AuthController::class, 'getNotifications']);

    // Technician Offers
    Route::prefix('technician')->group(function () {
        Route::get('/offers', [TechnicianOfferController::class, 'getMyOffers']);
        Route::post('/offers', [TechnicianOfferController::class, 'store']);
        Route::put('/offers/{id}', [TechnicianOfferController::class, 'update']);
        Route::delete('/offers/{id}', [TechnicianOfferController::class, 'destroy']);
        Route::get('/offers/{status}', [TechnicianOfferController::class, 'getOffersByStatus']);
        Route::post('/offers/{id}/cancel', [TechnicianOfferController::class, 'cancelOffer']);
        Route::get('/offers/{id}/location', [TechnicianOfferController::class, 'getOfferLocation']);
    });

    // Work Schedules
    Route::prefix('technician')->group(function () {
        Route::get('/work-schedules', [TechnicianWorkScheduleController::class, 'index']);
        Route::get('/work-schedules/{id}', [TechnicianWorkScheduleController::class, 'show']);
    });

    // Order Service
    Route::post('/order-service', [OrderServiceController::class, 'store']);
    Route::get('/order-services', [OrderServiceController::class, 'index']);
    Route::post('/order-services/{id}/complete', [OrderServiceController::class, 'completeOrder']);

    // Service Requests
    Route::post('/service-request', [ServiceRequestController::class, 'store']);
    Route::get('/service-requests', [ServiceRequestController::class, 'index']);

    // User Offers Management
    Route::prefix('user')->group(function () {
        Route::get('/offers', [UserOfferController::class, 'getUserOffers']);
        Route::post('/offers/{offerId}/accept', [UserOfferController::class, 'acceptOffer']);
        Route::post('/offers/{offerId}/reject', [UserOfferController::class, 'rejectOffer']);
        Route::post('/offers/{offerId}/complete', [UserOfferController::class, 'confirmCompletion']);
        Route::get('/offers/{status}', [UserOfferController::class, 'getOffersByStatus']);
    });

    // Offer Actions
    Route::prefix('offers')->group(function () {
        Route::post('/{offer}/reject', [UserOfferController::class, 'rejectOffer']);
        Route::post('/{offer}/complete', [UserOfferController::class, 'confirmCompletion']);
    });

    // Rating Routes
    Route::post('/Service-Rating', [RatingController::class, 'store']);
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

Route::middleware('auth:sanctum')->post('/scan-qr/{technicianId}', [AttendanceController::class, 'scanQr'])->name('scan-qr');

Route::get('/technician/qr/{id}', [TechnicianAuthController::class, 'showQr'])->middleware('auth:sanctum');


Route::post('/verify-id', [TechnicianAuthController::class, 'verifyIdentity']);



// Products
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/products/category/{category}', [ProductController::class, 'filterByCategory']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/technician/profile', [TechnicianAuthController::class, 'updateProfile']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/user/profile', [AuthController::class, 'updateProfile']);
});


Route::post('/paymob/payment', [PaymentController::class, 'createPaymobPayment'])->name('paymob.payment');
Route::post('/paymob/callback', [PaymentController::class, 'handlePaymobCallback'])->name('paymob.callback');
Route::get('/paymob/status', [PaymentController::class, 'checkPaymentStatus']);

Route::prefix('test/technician-wallet')->group(function (): void {
    Route::post('create', [TechnicianWalletController::class, 'createWallet']);
    Route::post('deposit', [TechnicianWalletController::class, 'deposit']);
    Route::post('withdraw/request', [TechnicianWalletController::class, 'requestWithdrawal']);
    Route::post('withdraw/complete', [TechnicianWalletController::class, 'completeWithdrawal']);
    Route::get('balance', [TechnicianWalletController::class, 'getBalance']);
    Route::get('transactions', [TechnicianWalletController::class, 'getTransactions']);
});
