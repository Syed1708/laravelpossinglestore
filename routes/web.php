<?php

use Illuminate\Support\Facades\Route;


use App\Http\Controllers\Admin\DailyClosureController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MenuEngineeringController;
use App\Http\Controllers\Admin\RecipeController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\PurchaseOrderController;

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
    // Custom Menu Engineering & Profitability route
    Route::get('/admin/menu-engineering', [MenuEngineeringController::class, 'index'])->name('admin.menu-engineering.index');
});

// routes/web.php

// 🚀 Dynamic Language Switcher Route
Route::get('/lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'fr'])) {
        session()->put('locale', $locale);
    }
    return redirect()->back();
})->name('lang.switch');