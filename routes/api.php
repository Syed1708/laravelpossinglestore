<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderSyncController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\StripeWebController;
use App\Http\Controllers\AuthController;



/*
|--------------------------------------------------------------------------
| 🚀 1. PUBLIC ROUTES (No Token Required)
|--------------------------------------------------------------------------
| Anyone in the world (including anonymous online customers) can view your 
| menu, create Stripe checkout sessions, and Stripe's servers can trigger webhooks.
*/
Route::get('/menu', [MenuController::class, 'index']); // 🚀 Moved outside auth:sanctum!
Route::post('/stripe/checkout-session', [StripeWebController::class, 'createCheckoutSession']);
Route::post('/stripe/webhook', [StripeWebController::class, 'handleWebhook']);


// Route::post('/login', [AuthController::class, 'login']);
// Route::post('/register', [AuthController::class, 'register']);

/*
|--------------------------------------------------------------------------
| 🛡️ 2. SECURE ROUTES (Sanctum Token Required)
|--------------------------------------------------------------------------
| Only authenticated cashiers using your physical tablet POS can access 
| these routes to sync completed tickets and read their profiles.
*/
Route::middleware('auth:sanctum')->group(function () {
    // Fetch  profile
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // POS Bulk Sync Route
    Route::post('/orders/sync', [OrderSyncController::class, 'sync']);
});

// Clients

Route::post('/client/register', [AuthController::class, 'register']);
Route::post('/client/login', [AuthController::class, 'login']);


// 3. Protected Client Routes (Using Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    
    // Get Current Logged-in Client Profile
    Route::get('/client/me', function (Request $request) {
        return $request->user();
    });

    // Get Logged-in Client's Orders
    Route::get('/client/orders', [AuthController::class, 'clientOrders']);
    Route::get('/client/profile', [AuthController::class, 'clientProfile']);
    
});