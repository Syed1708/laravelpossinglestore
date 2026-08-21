<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Helpers\StoreHoursHelper;

// Controllers
use App\Http\Controllers\Api\OrderSyncController;
use App\Http\Controllers\KioskOrderController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\Admin\OnlineOrderController;
use App\Http\Controllers\Api\v1\client\ClientAuthController;
use App\Http\Controllers\Api\v1\client\CouponApiController;
use App\Http\Controllers\Api\v1\client\StripeWebController;
use App\Http\Controllers\Api\v1\Pos\DayClosureApiController;
use App\Http\Controllers\Api\v1\Pos\PosSalesApiController;
use App\Http\Controllers\Api\v1\staff\AuthController;

/*
|--------------------------------------------------------------------------
| 🌐 1. PUBLIC STORE & CATALOG ROUTES (No Token Required)
|--------------------------------------------------------------------------
*/

// Real-Time Store Hours & Master Status Check
Route::get('/store-status', function () {
    $settings = StoreSetting::getSettings();

    return response()->json([
        'is_open'               => StoreHoursHelper::isOpen(),
        'is_store_open'         => (bool) $settings->is_store_open,
        'online_orders_enabled' => StoreHoursHelper::canAcceptOnlineOrders(),
        'reservations_enabled'  => StoreHoursHelper::canAcceptReservations(),
        'schedule'              => StoreHoursHelper::getScheduleText(),
        'closed_message'        => StoreHoursHelper::getClosedMessage(),
    ]);
});

// Full Site Branding & CMS Settings (Logo, Colors, Hero Banners)
Route::get('/site-settings', function () {
    $settings = StoreSetting::getSettings();

    return response()->json([
        ...$settings->toArray(),
        'logo_url'    => $settings->logo_path ? asset('storage/' . $settings->logo_path) : null,
        'favicon_url' => $settings->favicon_path ? asset('storage/' . $settings->favicon_path) : null,
    ]);
});

// 🚀 PUBLIC MENU CATALOG (Eager loads Kiosk Option Groups & Choices)
Route::get('/menu', function () {
    return response()->json(
        Product::where('is_active', true)
            ->with([
                'category',
                'optionGroups' => function ($q) {
                    $q->whereHas('options', fn($opt) => $opt->where('is_active', true))
                        ->with(['options' => fn($opt) => $opt->where('is_active', true)]);
                }
            ])
            ->get()
    );
});

Route::get('/products', function () {
    return response()->json(
        Product::where('is_active', true)
            ->with([
                'category',
                'optionGroups' => function ($q) {
                    $q->whereHas('options', fn($opt) => $opt->where('is_active', true))
                        ->with(['options' => fn($opt) => $opt->where('is_active', true)]);
                }
            ])
            ->get()
    );
});

// 🚀 SINGLE PRODUCT DETAILS WITH KIOSK STEPS
Route::get('/products/{product}', function (Product $product) {
    $product->load([
        'category',
        'optionGroups' => function ($q) {
            $q->whereHas('options', fn($opt) => $opt->where('is_active', true))
                ->with(['options' => fn($opt) => $opt->where('is_active', true)]);
        }
    ]);

    return response()->json([
        'success' => true,
        'data'    => $product
    ]);
});


/*
|--------------------------------------------------------------------------
| 🛍️ 2. PUBLIC CHECKOUT, COUPONS, KIOSK & RESERVATIONS
|--------------------------------------------------------------------------
*/

// Customer Authentication
Route::post('/client/register', [ClientAuthController::class, 'register']);
Route::post('/client/login', [ClientAuthController::class, 'login']);

// Stripe Checkout & Webhooks
Route::post('/stripe/checkout-session', [StripeWebController::class, 'createCheckoutSession']);
Route::get('/checkout/verify-session', [StripeWebController::class, 'verifySession']);
Route::post('/stripe/webhook', [StripeWebController::class, 'handleWebhook']);

// Promo Codes & Kiosk Checkout
Route::post('/coupons/validate', [CouponApiController::class, 'validateCoupon']);
Route::post('/kiosk/orders', [KioskOrderController::class, 'storeKioskOrder']);

// Customer Reservation Availability & Booking
Route::get('/reservations/availability', [ReservationController::class, 'checkAvailability']);
Route::post('/reservations/online', [ReservationController::class, 'storeOnline']);

// Staff / Admin Login
Route::post('/admin/login', [AuthController::class, 'login']);


/*
|--------------------------------------------------------------------------
| 🛡️ 3. PROTECTED ROUTES (Sanctum Token Required)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // -------------------------------------------------------------
    // A. CUSTOMER PORTAL ROUTES
    // -------------------------------------------------------------
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/client/me', function (Request $request) {
        return $request->user();
    });
    Route::get('/client/profile', [ClientAuthController::class, 'clientProfile']);
    Route::put('/client/profile', [ClientAuthController::class, 'clientProfile']);
    Route::get('/client/orders', [ClientAuthController::class, 'clientOrders']);

    // Customer Table Reservations
    Route::get('/client/reservations', [ReservationController::class, 'getClientReservations']);
    Route::post('/client/reservations/{reservation}/cancel', [ReservationController::class, 'cancelClientReservation']);


    // -------------------------------------------------------------
    // B. POS TERMINAL & HOSTESS ROUTES
    // -------------------------------------------------------------
    Route::get('/tables', function () {
        return response()->json(\App\Models\Table::where('is_active', true)->orderBy('table_number')->get());
    });

    // POS Order Sync
    Route::post('/orders/sync', [OrderSyncController::class, 'sync']);

    // POS Day Closures (Z-Reports)
    Route::get('/pos/z-closure/summary', [DayClosureApiController::class, 'getShiftSummary']);
    Route::post('/pos/z-closure/confirm', [DayClosureApiController::class, 'closeDay']);
    Route::get('/pos/z-closure/history', [DayClosureApiController::class, 'getClosureHistory']);

    // POS Kiosk Orders
    Route::get('/pos/kiosk-unpaid-orders', [KioskOrderController::class, 'getUnpaidKioskOrders']);
    Route::post('/pos/kiosk-orders/{order}/pay', [KioskOrderController::class, 'payKioskOrderAtCounter']);
    // POS Kiosk Order Cancellation
    Route::post('/pos/kiosk-orders/{order}/cancel', [KioskOrderController::class, 'cancelUnpaidKioskOrder']);
    // POS Sales History & Refunds
    Route::get('/pos/sales', [PosSalesApiController::class, 'getSalesHistory']);
    Route::get('/pos/sales/{id}', [PosSalesApiController::class, 'showOrderDetails']);
    Route::post('/pos/refund/{id}', [PosSalesApiController::class, 'refundOrder']);

    // Online Orders Dispatcher
    Route::get('/online-orders', [OnlineOrderController::class, 'getOnlineOrders']);
    Route::post('/online-orders/{order}/accept', [OnlineOrderController::class, 'acceptOrder']);
    Route::post('/online-orders/{order}/reject', [OnlineOrderController::class, 'rejectOrder']);

    // Hostess Reservations Manager
    Route::get('/reservations/by-date', [ReservationController::class, 'getReservationsByDate']);
    Route::post('/reservations/phone-booking', [ReservationController::class, 'storePhoneBooking']);
    Route::post('/reservations/{reservation}/status', [ReservationController::class, 'updateStatus']);
});
