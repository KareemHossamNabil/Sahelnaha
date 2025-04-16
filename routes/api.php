<?php

use App\Http\Controllers\API\AddressController;
use App\Http\Controllers\API\ServiceTypeController as APIServiceTypeController;
use App\Http\Controllers\ProblemTypeController;
use App\Http\Controllers\Apicontrollers\AuthController;
use App\Http\Controllers\BookingController;
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
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ServiceRequest\PaymentMethodController;
use App\Http\Controllers\ServiceRequest\ServiceTypeController;
use App\Http\Controllers\TechnicianOfferController;

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


// Products
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/products/category/{category}', [ProductController::class, 'filterByCategory']);

// Review for product
Route::post('/products/{id}/reviews', [ReviewController::class, 'store']);


// Cart Routes
Route::post('/cart/add/{productId}', [CartController::class, 'addToCart']);
Route::get('/cart', [CartController::class, 'viewCart']);
Route::delete('/cart/remove/{productId}', [CartController::class, 'removeFromCart']);
Route::post('/cart/update/{productId}', [CartController::class, 'updateQuantity']);
Route::delete('/cart/clear', [CartController::class, 'clearCart']);


// Here is All Routes Related to Order Service or Describe A problem

// Retireve The Service Types
Route::prefix('service-types')->group(function () {
    Route::get('/', [ServiceTypeController::class, 'index']);
    Route::get('/{id}', [ServiceTypeController::class, 'show']);
    Route::get('/category/{category}', [ServiceTypeController::class, 'getByCategory']);
});

// Payment Methods
Route::get('/payment-methods', [PaymentMethodController::class, 'index']);
Route::get('/payment-methods/{id}', [PaymentMethodController::class, 'show']);


Route::get('/offers', [TechnicianOfferController::class, 'index']);
Route::post('/offers', [TechnicianOfferController::class, 'store']);
Route::get('/offers/{id}', [TechnicianOfferController::class, 'show']);
Route::put('/offers/{id}', [TechnicianOfferController::class, 'update']);
Route::delete('/offers/{id}', [TechnicianOfferController::class, 'destroy']);
Route::get('/technician/offers', [TechnicianOfferController::class, 'getTechnicianOffers']);



Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
