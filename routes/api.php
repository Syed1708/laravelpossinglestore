<?php

use Illuminate\Support\Facades\Route;
use App\Models\Product;
use App\Models\Table;

// Controllers
use App\Http\Controllers\Admin\OnlineOrderController;
use App\Http\Controllers\Api\v1\catalog\KioskOrderController;
use App\Http\Controllers\Api\v1\catalog\ReservationApiController;
use App\Http\Controllers\Api\v1\client\ClientAuthController;
use App\Http\Controllers\Api\v1\client\CouponApiController;
use App\Http\Controllers\Api\v1\client\SiteSettingsApiController;
use App\Http\Controllers\Api\v1\client\StripeWebController;
use App\Http\Controllers\Api\v1\kds\KdsApiController;
use App\Http\Controllers\Api\v1\Pos\DayClosureApiController;
use App\Http\Controllers\Api\v1\Pos\OrderSyncController;
use App\Http\Controllers\Api\v1\Pos\PosSalesApiController;
use App\Http\Controllers\Api\v1\staff\AuthController;

/*
|--------------------------------------------------------------------------
| 🚀 API VERSION 1 (Base URL: http://127.0.0.1:8000/api/v1)
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | 🌐 1. PUBLIC STORE & CATALOG ROUTES (No Token Required)
    |--------------------------------------------------------------------------
    */

    // Real-Time Store Hours & Master Status Check
    Route::get('/store-status', [SiteSettingsApiController::class, 'storeStatus']);

    // Full Site Branding, Hero Sliders & CMS Settings
    Route::get('/site-settings', [SiteSettingsApiController::class, 'siteSettings']);

    // Public Menu Catalog (Eager loads Kiosk Option Groups & Choices)
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

    // Single Product Details With Steps
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

    // Customer Auth
    Route::prefix('client')->middleware(['throttle:5,1'])->group(function () {
        Route::post('/register', [ClientAuthController::class, 'register']);
        Route::post('/login', [ClientAuthController::class, 'login']);
        Route::post('/forgot-password', [ClientAuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [ClientAuthController::class, 'resetPassword']);
    });

    // Staff / Admin Login
    Route::prefix('admin')->middleware(['throttle:5,1'])->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
    });

    // Stripe Checkout & Webhooks
    Route::post('/stripe/checkout-session', [StripeWebController::class, 'createCheckoutSession']);
    Route::get('/checkout/verify-session', [StripeWebController::class, 'verifySession']);
    Route::post('/stripe/webhook', [StripeWebController::class, 'handleWebhook']);

    // Promo Codes & Kiosk Checkout
    Route::post('/coupons/validate', [CouponApiController::class, 'validateCoupon'])->middleware(['throttle:5,1']);
    Route::post('/kiosk/orders', [KioskOrderController::class, 'storeKioskOrder']);

    // Public Table Availability & Booking
    Route::get('/reservations/availability', [ReservationApiController::class, 'checkAvailability']);
    Route::post('/reservations/online', [ReservationApiController::class, 'storeOnline']);

    /*
    |--------------------------------------------------------------------------
    | 🛡️ 3. PROTECTED ROUTES (Sanctum Token Required)
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {

        // --- A. CUSTOMER PORTAL ---
        Route::prefix('client')->middleware(['throttle:5,1'])->group(function () {
            Route::get('/profile', [ClientAuthController::class, 'clientProfile']);
            Route::put('/profile', [ClientAuthController::class, 'updateProfile']);
            Route::get('/orders', [ClientAuthController::class, 'clientOrders']);
            Route::post('/logout', [ClientAuthController::class, 'logout']);

            // Customer Table Reservations
            Route::get('/reservations', [ReservationApiController::class, 'getClientReservations']);
            Route::post('/reservations/{reservation}/cancel', [ReservationApiController::class, 'cancelClientReservation']);
        });

        // --- B. WEB POS & REGISTER OPERATIONS ---
        Route::prefix('pos')->middleware(['throttle:5,1'])->group(function () {
            Route::post('/orders/sync', [OrderSyncController::class, 'sync']);

            // Day Closures (Z-Reports)
            Route::get('/z-closure/summary', [DayClosureApiController::class, 'getShiftSummary']);
            Route::post('/z-closure/confirm', [DayClosureApiController::class, 'closeDay']);
            Route::get('/z-closure/history', [DayClosureApiController::class, 'getClosureHistory']);

            // Unpaid Kiosk Counter Processing
            Route::get('/kiosk-unpaid-orders', [KioskOrderController::class, 'getUnpaidKioskOrders']);
            Route::post('/kiosk-orders/{order}/pay', [KioskOrderController::class, 'payKioskOrderAtCounter']);
            Route::post('/kiosk-orders/{order}/cancel', [KioskOrderController::class, 'cancelUnpaidKioskOrder']);

            // Sales History & Refunds
            Route::get('/sales', [PosSalesApiController::class, 'getSalesHistory']);
            Route::get('/sales/{id}', [PosSalesApiController::class, 'showOrderDetails']);
            Route::post('/refund/{id}', [PosSalesApiController::class, 'refundOrder']);
        });

        // --- C. KITCHEN DISPLAY SYSTEM (KDS) API ---
        Route::prefix('kds')->middleware(['throttle:5,1'])->group(function () {
            Route::get('/orders/chef', [KdsApiController::class, 'getChefOrders']);
            Route::get('/orders/packer', [KdsApiController::class, 'getPackerOrders']);
            Route::post('/orders/{order}/status', [KdsApiController::class, 'updateOrderStatus']);
            Route::post('/items/{item}/toggle', [KdsApiController::class, 'toggleItemStatus']);
        });

        // --- D. HOSTESS & ONLINE DISPATCHER ---
        Route::get('/reservations/by-date', [ReservationApiController::class, 'getReservationsByDate']);
        Route::post('/reservations/phone-booking', [ReservationApiController::class, 'storePhoneBooking']);
        Route::post('/reservations/{reservation}/status', [ReservationApiController::class, 'updateStatus']);

        Route::get('/online-orders', [OnlineOrderController::class, 'getOnlineOrders']);
        Route::post('/online-orders/{order}/accept', [OnlineOrderController::class, 'acceptOrder']);
        Route::post('/online-orders/{order}/reject', [OnlineOrderController::class, 'rejectOrder']);

        Route::get('/tables', fn() => response()->json(Table::where('is_active', true)->orderBy('table_number')->get()));
    });
});