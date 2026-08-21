<?php

use Illuminate\Support\Facades\Route;

// Admin Controllers
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OnlineOrderController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\KdsController;
use App\Http\Controllers\Admin\RecipeController;
use App\Http\Controllers\Admin\ProductStepController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\PnlReportController;
use App\Http\Controllers\Admin\MenuEngineeringController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\DailyClosureController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\ReservationController;

/*
|--------------------------------------------------------------------------
| 🌐 1. PUBLIC WEB ROUTES
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

// Dynamic Language Switcher Route
Route::get('/lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'fr'])) {
        session()->put('locale', $locale);
    }
    return redirect()->back();
})->name('lang.switch');


/*
|--------------------------------------------------------------------------
| 🛡️ 2. AUTHENTICATED ADMIN DASHBOARD ROUTES
|--------------------------------------------------------------------------
| All admin, cashier, kitchen, and hostess screens share a single, clean,
| session-protected middleware group.
*/

Route::middleware(['web', 'auth'])->group(function () {

    // 🚀 Dashboard Command Center (Overrides Tyro's default home route)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('tyro-dashboard.index');

    // -------------------------------------------------------------
    // 📦 A. MANAGE ORDERS & DISPATCHER
    // -------------------------------------------------------------
    Route::prefix('admin')->group(function () {
        Route::get('/orders/online-management', [OnlineOrderController::class, 'index'])->name('admin.orders.online');
        Route::get('/orders/online-grid', function () {
            return view('admin.orders.onlinegrid');
        })->name('admin.orders.online-grid');

        // Orders Archive
        Route::get('/orders-archive', [OrderController::class, 'index'])->name('admin.orders.index');

        // Admin Online Dispatcher API Endpoints
        Route::get('/api/online-orders', [OnlineOrderController::class, 'getOnlineOrders']);
        Route::post('/api/online-orders/{order}/accept', [OnlineOrderController::class, 'acceptOrder']);
        Route::post('/api/online-orders/{order}/reject', [OnlineOrderController::class, 'rejectOrder']);
    });

    // -------------------------------------------------------------
    // 👨‍🍳 B. KITCHEN DISPLAY SYSTEM (KDS)
    // -------------------------------------------------------------
    Route::prefix('admin/kds')->group(function () {
        Route::get('/chef', [KdsController::class, 'chefIndex'])->name('admin.kds.chef');
        Route::get('/packer', [KdsController::class, 'packerIndex'])->name('admin.kds.packer');

        // Route::post('/api/orders/{order}/status', [KdsController::class, 'updateOrderStatus'])->name('admin.kds.order.update');
    });

    // 🚀 KDS Order Status Update Route with out admin prefix
    // KDS API Endpoints
    Route::get('/api/orders/chef', [KdsController::class, 'getChefOrders'])->name('admin.kds.orders.chef');
    Route::get('/api/orders/packer', [KdsController::class, 'getPackerOrders'])->name('admin.kds.orders.packer');
    Route::post('/api/kds/orders/{order}/status', [App\Http\Controllers\Admin\KdsController::class, 'updateOrderStatus'])->name('admin.kds.order.update');
    Route::post('/api/kds/items/{item}/toggle', [KdsController::class, 'toggleItemStatus'])->name('admin.kds.item.toggle');

    // -------------------------------------------------------------
    // 🗺️ C. TABLE FLOOR PLAN & HOSTESS RESERVATIONS
    // -------------------------------------------------------------
    Route::prefix('admin')->group(function () {
        Route::get('/reservations/floor-plan', [ReservationController::class, 'floorPlan'])->name('admin.reservations.floor_plan');

        // Hostess API Endpoints
        Route::get('/api/reservations/by-date', [ReservationController::class, 'getReservationsByDate']);
        Route::post('/api/reservations/phone-booking', [ReservationController::class, 'storePhoneBooking']);
        Route::post('/api/reservations/{reservation}/status', [ReservationController::class, 'updateStatus']);
    });

    // -------------------------------------------------------------
    // 📖 D. RECIPE BUILDER & PRODUCT KIOSK STEPS
    // -------------------------------------------------------------
    Route::prefix('admin')->group(function () {
        // Recipes & Food Costing
        Route::get('/recipes', [RecipeController::class, 'index'])->name('admin.recipes.index');
        Route::get('/recipes/{product}', [RecipeController::class, 'show'])->name('admin.recipes.show');
        Route::post('/recipes/{product}/ingredients', [RecipeController::class, 'store'])->name('admin.recipes.store');
        Route::delete('/recipes/{product}/ingredients/{recipe}', [RecipeController::class, 'destroy'])->name('admin.recipes.destroy');

        // Product Kiosk Steps Manager
        Route::get('/product-steps', [ProductStepController::class, 'index'])->name('admin.product_steps.index');
        Route::get('/product-steps/{product}', [ProductStepController::class, 'show'])->name('admin.product_steps.show');
        Route::post('/product-steps/{product}', [ProductStepController::class, 'store'])->name('admin.product_steps.store');
        Route::delete('/product-steps/{product}/{optionGroup}', [ProductStepController::class, 'destroy'])->name('admin.product_steps.destroy');
    });

    // -------------------------------------------------------------
    // 📦 E. SUPPLIER PURCHASES & DELIVERIES
    // -------------------------------------------------------------
    Route::prefix('admin')->group(function () {
        Route::get('/purchases', [PurchaseOrderController::class, 'index'])->name('admin.purchases.index');
        Route::get('/purchases/create', [PurchaseOrderController::class, 'create'])->name('admin.purchases.create');
        Route::post('/purchases', [PurchaseOrderController::class, 'store'])->name('admin.purchases.store');
        Route::get('/purchases/{purchase}', [PurchaseOrderController::class, 'show'])->name('admin.purchases.show');
        Route::post('/purchases/{purchase}/receive', [PurchaseOrderController::class, 'receive'])->name('admin.purchases.receive');
        Route::post('/purchases/{purchase}/cancel', [PurchaseOrderController::class, 'cancel'])->name('admin.purchases.cancel');
        Route::delete('/purchases/{purchase}', [PurchaseOrderController::class, 'destroy'])->name('admin.purchases.destroy');
    });

    // -------------------------------------------------------------
    // 📈 F. FINANCIAL REPORTS & ANALYTICS
    // -------------------------------------------------------------
    Route::prefix('admin')->group(function () {
        // Executive P&L Financials
        Route::get('/reports/pnl', [PnlReportController::class, 'index'])->name('admin.reports.pnl');

        // General Reports & PDF Downloads
        Route::get('/reports', [ReportController::class, 'index'])->name('admin.reports.index');
        Route::get('/reports/pdf', [ReportController::class, 'downloadPdf'])->name('admin.reports.pdf');

        // AI Menu Engineering Matrix
        Route::get('/menu-engineering', [MenuEngineeringController::class, 'index'])->name('admin.menu_engineering.index');

        // Z-Closure Actions
        Route::post('/closures/close', [DailyClosureController::class, 'closeDay'])->name('admin.closures.close');
    });

    // -------------------------------------------------------------
    // ⚙️ G. MODULAR SITE & OPERATIONS SETTINGS
    // -------------------------------------------------------------
    Route::prefix('admin/settings')->group(function () {
        // General Store & Operations
        Route::get('/general', [AdminSettingsController::class, 'general'])->name('admin.settings.general');
        Route::put('/general', [AdminSettingsController::class, 'updateGeneral'])->name('admin.settings.update_general');

        // Homepage Builder
        Route::get('/homepage', [AdminSettingsController::class, 'homepage'])->name('admin.settings.homepage');
        Route::put('/homepage', [AdminSettingsController::class, 'updateHomepage'])->name('admin.settings.update_homepage');

        // Web Theme & Branding
        Route::get('/theme', [AdminSettingsController::class, 'theme'])->name('admin.settings.theme');
        Route::put('/theme', [AdminSettingsController::class, 'updateTheme'])->name('admin.settings.update_theme');
    });
});
