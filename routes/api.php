<?php

use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderSyncController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    // Other routes can go here...
    
    // POS Bulk Sync Route
    Route::post('/orders/sync', [OrderSyncController::class, 'sync']);

    Route::get('/menu', [MenuController::class, 'index']);

});