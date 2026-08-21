<?php

use App\Http\Controllers\Admin\AdminSettingsController;
use Illuminate\Support\Facades\Route;


use App\Http\Controllers\Admin\DailyClosureController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\KdsController;
use App\Http\Controllers\Admin\MenuEngineeringController;
use App\Http\Controllers\Admin\OnlineOrderController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PnlReportController;
use App\Http\Controllers\Admin\ProductStepController;
use App\Http\Controllers\Admin\RecipeController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\ReservationController;

Route::get('/', function () {
    return view('welcome');
});


// 🚀 Override Tyro's default home route to display our custom POS analytics
Route::middleware(['web', 'auth'])
    ->get('/dashboard', [DashboardController::class, 'index'])
    ->name('tyro-dashboard.index'); // 🚀 This name must match Tyro's configuration!

// Z-Report Actions
Route::middleware(['web', 'auth'])->group(function () {
    Route::post('/admin/closures/close', [DailyClosureController::class, 'closeDay'])->name('admin.closures.close');
});


Route::middleware(['web', 'auth'])->group(function () {
    // Reports dashboard
    Route::get('/admin/reports', [ReportController::class, 'index'])->name('admin.reports.index');

    // PDF generator route
    Route::get('/admin/reports/pdf', [ReportController::class, 'downloadPdf'])->name('admin.reports.pdf');
});


Route::middleware(['web', 'auth'])->group(function () {
    // Custom Recipe Builder routes
    Route::get('/admin/recipes', [RecipeController::class, 'index'])->name('admin.recipes.index');
    Route::get('/admin/recipes/{product}', [RecipeController::class, 'show'])->name('admin.recipes.show');
    Route::post('/admin/recipes/{product}/ingredients', [RecipeController::class, 'store'])->name('admin.recipes.store');
    Route::delete('/admin/recipes/{product}/ingredients/{recipe}', [RecipeController::class, 'destroy'])->name('admin.recipes.destroy');
});



Route::middleware(['web', 'auth'])->group(function () {
    // Custom Purchase Order CRUD
    Route::get('/admin/purchases', [PurchaseOrderController::class, 'index'])->name('admin.purchases.index');
    Route::get('/admin/purchases/create', [PurchaseOrderController::class, 'create'])->name('admin.purchases.create');
    Route::post('/admin/purchases', [PurchaseOrderController::class, 'store'])->name('admin.purchases.store');
    Route::get('/admin/purchases/{purchase}', [PurchaseOrderController::class, 'show'])->name('admin.purchases.show');
    Route::post('/admin/purchases/{purchase}/receive', [PurchaseOrderController::class, 'receive'])->name('admin.purchases.receive');
    Route::delete('/admin/purchases/{purchase}', [PurchaseOrderController::class, 'destroy'])->name('admin.purchases.destroy');
    // 🚀 NEW: Cancel/Reject Delivery Route
    Route::post('/admin/purchases/{purchase}/cancel', [PurchaseOrderController::class, 'cancel'])->name('admin.purchases.cancel');
});

Route::middleware(['web', 'auth'])->group(function () {

    // 🤖 AI Menu Engineering Matrix Route
    Route::get('/admin/menu-engineering', [MenuEngineeringController::class, 'index'])->name('admin.menu_engineering.index');
});

Route::prefix('admin')->middleware(['auth'])->group(function () {
    Route::get('/orders/online-management', [OnlineOrderController::class, 'index'])->name('admin.orders.online');
    Route::get('/orders/online-grid', function () {
        return view('admin.orders.onlinegrid');
    })->name('admin.orders.online-grid');

    // 🚀 Admin API Endpoints (Authorized via Web Session Cookie)
    Route::get('/api/online-orders', [OnlineOrderController::class, 'getOnlineOrders']);
    Route::post('/api/online-orders/{order}/accept', [OnlineOrderController::class, 'acceptOrder']);
    Route::post('/api/online-orders/{order}/reject', [OnlineOrderController::class, 'rejectOrder']);
});

// routes/web.php

// 🚀 Dynamic Language Switcher Route
Route::get('/lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'fr'])) {
        session()->put('locale', $locale);
    }
    return redirect()->back();
})->name('lang.switch');


Route::middleware(['web', 'auth'])->group(function () {
    // ... other routes ...

    // Real-Time Kitchen Display Views
    Route::get('/dashboard/kds/chef', [KdsController::class, 'chefIndex'])->name('admin.kds.chef');
    Route::get('/dashboard/kds/packer', [KdsController::class, 'packerIndex'])->name('admin.kds.packer');
    Route::get('/dashboard/orders-archive', [OrderController::class, 'index'])->name('admin.orders.index');

    // ⚙️ Product Kiosk Step Builder Routes
    Route::get('/product-steps', [ProductStepController::class, 'index'])->name('admin.product_steps.index');
    Route::get('/product-steps/{product}', [ProductStepController::class, 'show'])->name('admin.product_steps.show');
    Route::post('/product-steps/{product}', [ProductStepController::class, 'store'])->name('admin.product_steps.store');
    Route::delete('/product-steps/{product}/{optionGroup}', [ProductStepController::class, 'destroy'])->name('admin.product_steps.destroy');

    // 🗺️ Floor Plan & Hostess Screen
    Route::get('/dashboard/reservations/floor-plan', [ReservationController::class, 'floorPlan'])->name('admin.reservations.floor_plan');

    Route::get('/reports/pnl', [PnlReportController::class, 'index'])->name('admin.reports.pnl');

    // ⚙️ 1. General & Operations Settings
    Route::get('/settings/general', [AdminSettingsController::class, 'general'])->name('admin.settings.general');
    Route::put('/settings/general', [AdminSettingsController::class, 'updateGeneral'])->name('admin.settings.update_general');

    // 🏠 2. Homepage Builder Settings
    Route::get('/settings/homepage', [AdminSettingsController::class, 'homepage'])->name('admin.settings.homepage');
    Route::put('/settings/homepage', [AdminSettingsController::class, 'updateHomepage'])->name('admin.settings.update_homepage');

    // 🎨 3. Theme & UI Settings
    Route::get('/settings/theme', [AdminSettingsController::class, 'theme'])->name('admin.settings.theme');
    Route::put('/settings/theme', [AdminSettingsController::class, 'updateTheme'])->name('admin.settings.update_theme');

    // API endpoints for real-time WebSocket payload fetching
    Route::get('/api/kds/orders/chef', [KdsController::class, 'getChefOrders'])->name('admin.kds.orders.chef');
    Route::get('/api/kds/orders/packer', [KdsController::class, 'getPackerOrders'])->name('admin.kds.orders.packer');

    // Status update endpoints
    Route::post('/api/kds/items/{item}/toggle', [KdsController::class, 'toggleItemStatus'])->name('admin.kds.item.toggle');
    Route::post('/api/kds/orders/{order}/status', [KdsController::class, 'updateOrderStatus'])->name('admin.kds.order.update');
});

Route::prefix('admin')->middleware(['web', 'auth'])->group(function () {
    Route::get('/api/reservations/by-date', [ReservationController::class, 'getReservationsByDate']);
    Route::post('/api/reservations/phone-booking', [ReservationController::class, 'storePhoneBooking']);
    Route::post('/api/reservations/{reservation}/status', [ReservationController::class, 'updateStatus']);
});
