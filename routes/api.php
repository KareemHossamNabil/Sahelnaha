<?php

use App\Http\Controllers\Apicontrollers\AuthController;
use App\Http\Controllers\SocialiteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\UsersReviewController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\TashtibaController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ReviewController;



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

// Products of the Market
// Route::apiResource('products', ProductController::class);


// Retrived All Products
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/products/category/{category}', [ProductController::class, 'filterByCategory']);

// Add Rate and Review for product
Route::post('/products/{id}/reviews', [ReviewController::class, 'store']);

// انشاء الطلب
Route::post('/orders', [OrderController::class, 'store']);


// cart routes - need Authentication
Route::get('/cart', [CartController::class, 'index']);
Route::post('/cart', [CartController::class, 'store']);
Route::put('/cart/{id}', [CartController::class, 'update']);
Route::delete('/cart/{id}', [CartController::class, 'destroy']);
Route::delete('/cart', [CartController::class, 'clearCart']);








Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
