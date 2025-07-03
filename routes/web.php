<?php

use App\Helpers\FcmHelper;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SocialiteController;
use App\Http\Controllers\TechnicianSocialiteController;
use App\Http\Controllers\StaticPageController;

Route::get('/auth/google/redirect', [SocialiteController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [SocialiteController::class, 'handleGoogleCallback']);

Route::get('/auth/facebook/redirect', [SocialiteController::class, 'redirectToFacebook']);
Route::get('/auth/facebook/callback', [SocialiteController::class, 'handleFacebookCallback']);

// مسارات السوشيال للفنيين
// Route::prefix('api/technician')->group(function () {
//     Route::get('/auth/google/redirect', [TechnicianSocialiteController::class, 'redirectToGoogle']);
//     Route::get('/auth/google/callback', [TechnicianSocialiteController::class, 'handleGoogleCallback']);

//     Route::get('/auth/facebook/redirect', [TechnicianSocialiteController::class, 'redirectToFacebook']);
//     Route::get('/auth/facebook/callback', [TechnicianSocialiteController::class, 'handleFacebookCallback']);
// });

Route::get('/debug-routes', function () {
    $routes = collect(Route::getRoutes())->map(function ($route) {
        return [
            'uri' => $route->uri,
            'action' => $route->action['controller'] ?? null,
            'name' => $route->action['as'] ?? null,
        ];
    });

    return response()->json($routes);
});

Route::get('/', function () {
    return view('welcome');
});



// الصفحات الثابتة
Route::get('/privacy-policy', [StaticPageController::class, 'privacyPolicy'])->name('privacy');
Route::get('/data-deletion', [StaticPageController::class, 'dataDeletion'])->name('data-deletion');
Route::get('/terms-of-service', [StaticPageController::class, 'termsOfService'])->name('terms');
