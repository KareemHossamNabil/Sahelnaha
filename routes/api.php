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
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\TechnicianOfferController;
use App\Http\Controllers\Apicontrollers\TechnicianAuthController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\TechnicianWorkScheduleController;
use App\Http\Controllers\OrderServiceController;
use App\Http\Controllers\UserOfferController;
use App\Http\Controllers\UserNotificationController;

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

// Social Authentication
Route::get('auth/{provider}/redirect', [SocialAuthController::class, 'redirectToProvider']);
Route::get('auth/{provider}/callback', [SocialAuthController::class, 'handleProviderCallback']);

// Products
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/products/category/{category}', [ProductController::class, 'filterByCategory']);

// Service Requests
Route::get('service-requests', [ServiceRequestController::class, 'index']);
Route::get('service-request/{id}', [ServiceRequestController::class, 'getServiceRequestById']);

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
    // Route::get('/user/my-notifications', [UserNotificationController::class, 'index']);

    // Technician Offers
    Route::prefix('technician')->group(function () {
        Route::get('/offers', [TechnicianOfferController::class, 'getMyOffers']);
        Route::post('/offers', [TechnicianOfferController::class, 'store']);
        Route::put('/offers/{id}', [TechnicianOfferController::class, 'update']);
        Route::delete('/offers/{id}', [TechnicianOfferController::class, 'destroy']);
        Route::get('/offers/{status}', [TechnicianOfferController::class, 'getOffersByStatus']);
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
});
