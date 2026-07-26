<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderSyncController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\StripeWebController;
use App\Models\Client;
use App\Models\Order;
use Illuminate\Support\Facades\Hash;

// 1. Client Register
Route::post('/client/register', function (Request $request) {
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:clients,email',
        'password' => 'required|string|min:8',
        'phone' => 'nullable|string',
    ]);

    $client = Client::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'phone' => $request->phone,
    ]);

    $token = $client->createToken('client-web-token')->plainTextToken;

    return response()->json([
        'client' => [
            'id' => $client->id,
            'name' => $client->name,
            'email' => $client->email,
            'phone' => $client->phone,
        ],
        'token' => $token,
    ]);
});

// 2. Client Login
Route::post('/client/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $client = Client::where('email', $request->email)->first();

    if (!$client || !Hash::check($request->password, $client->password)) {
        return response()->json(['message' => 'Invalid email or password'], 401);
    }

    $token = $client->createToken('client-web-token')->plainTextToken;

    return response()->json([
        'client' => [
            'id' => $client->id,
            'name' => $client->name,
            'email' => $client->email,
            'phone' => $client->phone,
        ],
        'token' => $token,
    ]);
});

// 3. Protected Client Routes (Using Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    
    // Get Current Logged-in Client Profile
    Route::get('/client/me', function (Request $request) {
        return $request->user();
    });

    // Get Logged-in Client's Orders
    Route::get('/client/orders', function (Request $request) {
        $client = $request->user(); // Returns current Client
        
        $orders = Order::where('client_id', $client->id)
            ->with('items') // assuming items relationship exists
            ->latest()
            ->get();

        return response()->json($orders);
    });

        // PUT /api/client/profile
    Route::put('/client/profile', function (Request $request) {
        $client = $request->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
        ]);

        $client->update($request->only('name', 'phone', 'address'));

        return response()->json([
            'success' => true,
            'client' => $client
        ]);
    });
});

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