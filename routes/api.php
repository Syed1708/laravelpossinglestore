<?php

use App\Helpers\StoreHoursHelper;
use App\Http\Controllers\Admin\OnlineOrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderSyncController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\v1\client\ClientAuthController;
use App\Http\Controllers\Api\v1\client\StripeWebController;
use App\Http\Controllers\Api\v1\Pos\DayClosureApiController;
use App\Http\Controllers\Api\v1\Pos\PosSalesApiController;
use App\Http\Controllers\Api\v1\staff\AuthController;
use App\Http\Controllers\ReservationController;
use App\Models\Product;

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
// 🚀 Stripe Verification Endpoint (Public)
Route::get('/checkout/verify-session', [StripeWebController::class, 'verifySession']);


Route::post('/admin/login', [AuthController::class, 'login']);
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
    Route::get('/tables', function () {
        return response()->json(\App\Models\Table::where('is_active', true)->orderBy('table_number')->get());
    });


    // POS Bulk Sync Route
    Route::post('/orders/sync', [OrderSyncController::class, 'sync']);
    // z-closure
    Route::get('/pos/z-closure/summary', [DayClosureApiController::class, 'getShiftSummary']);
    Route::post('/pos/z-closure/confirm', [DayClosureApiController::class, 'closeDay']);
    Route::get('/pos/z-closure/history', [DayClosureApiController::class, 'getClosureHistory']);


    // 🚀 POS Sales History & Refunds
    Route::get('/pos/sales', [PosSalesApiController::class, 'getSalesHistory']);
    Route::get('/pos/sales/{id}', [PosSalesApiController::class, 'showOrderDetails']);
    Route::post('/pos/refund/{id}', [PosSalesApiController::class, 'refundOrder']);
});


// In routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/client/reservations', [ReservationController::class, 'getClientReservations']);
    Route::post('/client/reservations/{reservation}/cancel', [ReservationController::class, 'cancelClientReservation']);
});
// Clients

Route::post('/client/register', [ClientAuthController::class, 'register']);
Route::post('/client/login', [ClientAuthController::class, 'login']);


// 3. Protected Client Routes (Using Sanctum)
Route::middleware('auth:sanctum')->group(function () {

    // Get Current Logged-in Client Profile
    Route::get('/client/me', function (Request $request) {
        return $request->user();
    });

    // Get Logged-in Client's Orders
    Route::get('/client/orders', [ClientAuthController::class, 'clientOrders']);
    Route::get('/client/profile', [ClientAuthController::class, 'clientProfile']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/online-orders', [OnlineOrderController::class, 'getOnlineOrders']);
    Route::post('/online-orders/{order}/accept', [OnlineOrderController::class, 'acceptOrder']);
    Route::post('/online-orders/{order}/reject', [OnlineOrderController::class, 'rejectOrder']);
});



Route::get('/store-status', function () {
    $settings = \App\Models\StoreSetting::getSettings();

    return response()->json([
        'is_open'               => StoreHoursHelper::isOpen(),              // Open right now at this minute
        'is_store_open'         => (bool) $settings->is_store_open,         // Master Admin Toggle
        'online_orders_enabled' => StoreHoursHelper::canAcceptOnlineOrders(),
        'reservations_enabled'  => StoreHoursHelper::canAcceptReservations(), // Master ON + Reservations ON
        'schedule'              => StoreHoursHelper::getScheduleText(),
        'closed_message'        => StoreHoursHelper::getClosedMessage(),
    ]);
});
// Public Customer Reservation Endpoints
Route::get('/reservations/availability', [ReservationController::class, 'checkAvailability']);
Route::post('/reservations/online', [ReservationController::class, 'storeOnline']);

// Authenticated Staff / POS Endpoints
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/reservations/by-date', [ReservationController::class, 'getReservationsByDate']);
    Route::post('/reservations/phone-booking', [ReservationController::class, 'storePhoneBooking']);
    Route::post('/reservations/{reservation}/status', [ReservationController::class, 'updateStatus']);
});


// 🚀 PUBLIC SINGLE PRODUCT DETAILS ENDPOINT
Route::get('/products/{product}', function (Product $product) {
    return response()->json([
        'success' => true,
        'data'    => $product
    ]);
});
