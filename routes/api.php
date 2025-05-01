<?php

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
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ServiceRequest\AddressController;
use App\Http\Controllers\ServiceRequest\PaymentMethodController;
use App\Http\Controllers\ServiceRequest\ServiceTypeController;
use App\Http\Controllers\TechnicianOfferController;

use App\Http\Controllers\Apicontrollers\LoginController;
use App\Http\Controllers\Apicontrollers\RegisterController;
use App\Http\Controllers\Apicontrollers\TechnicianAuthController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\ServiceRequest\TimeSlotController;
use App\Http\Controllers\TechnicianWorkScheduleController;
use App\Http\Controllers\OrderServiceController;
use App\Http\Controllers\UserOfferController;

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


// Technician Authentication 
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
Route::middleware('auth:sanctum')->post('/products/{id}/reviews', [ProductReviewController::class, 'store']);

// Routes for the Service Request Process
Route::middleware('auth:sanctum')->group(function () {
    // Routes for the service request process
    Route::post('/service-request/step-one', [ServiceRequestController::class, 'storeStepOne']);
    Route::post('/service-request/step-two/{id}', [ServiceRequestController::class, 'storeStepTwo']);
    Route::post('/service-request/step-three/{id}', [ServiceRequestController::class, 'storeStepThree']);
});

Route::get('service-requests', [ServiceRequestController::class, 'index']);
Route::get('service-request/{id}', [ServiceRequestController::class, 'getServiceRequestById']);


Route::middleware('auth:sanctum')->get('/technician/my-notifications', [TechnicianAuthController::class, 'getNotifications']);

// Technician Offer
// Techincian Offers Not For Home Page
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/technician-offers', [TechnicianOfferController::class, 'store']);
    Route::put('/technician-offers/{id}', [TechnicianOfferController::class, 'update']);
    Route::delete('/technician-offers/{id}', [TechnicianOfferController::class, 'destroy']);
    Route::get('/technician/my-offers', [TechnicianOfferController::class, 'getMyOffers']);
});

// User deals with The Technician Offers
Route::middleware(['auth:sanctum'])->prefix('user')->group(function () {

    Route::get('service-requests/{serviceRequestId}/offers', [UserOfferController::class, 'getOffersByServiceRequest']);
    Route::post('offers/{offerId}/accept', [UserOfferController::class, 'acceptOffer']);
    Route::post('offers/{offerId}/cancel', [UserOfferController::class, 'cancelAcceptedOffer']);
    Route::post('offers/{offerId}/confirm', [UserOfferController::class, 'confirmOffer']);
    Route::get('offers/accepted', [UserOfferController::class, 'getMyAcceptedOffers']);
    Route::get('offers/completed', [UserOfferController::class, 'getMyCompletedOffers']);
});

// Technician Work Schedule -->> "It's Depend on the response from the User"
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/technician/work-schedules', [TechnicianWorkScheduleController::class, 'index']);
    Route::get('/technician/work-schedules/{id}', [TechnicianWorkScheduleController::class, 'show']);
});


Route::middleware('auth:sanctum')->post('/order-services', [OrderServiceController::class, 'store']);;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
