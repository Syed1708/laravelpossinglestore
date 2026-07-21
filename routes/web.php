<?php

use Illuminate\Support\Facades\Route;


use App\Http\Controllers\Admin\DailyClosureController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ReportController;

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