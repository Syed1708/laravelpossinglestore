<?php

namespace App\Http\Controllers\Api\v1\client;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class ClientAuthController extends Controller
{
    /**
     * Register a new customer account
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:clients,email'],
            'password' => ['required', 'string', PasswordRule::min(8)],
            'phone'    => ['nullable', 'string', 'max:50'],
            'address'  => ['nullable', 'string', 'max:500'],
        ]);

        $client = Client::create([
            'name'           => $validated['name'],
            'email'          => strtolower(trim($validated['email'])),
            'password'       => Hash::make($validated['password']),
            'phone'          => $validated['phone'] ?? null,
            'address'        => $validated['address'] ?? null,
            'loyalty_points' => 0,
        ]);

        $token = $client->createToken('client-web-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Account registered successfully.',
            'client'  => [
                'id'             => $client->id,
                'name'           => $client->name,
                'email'          => $client->email,
                'phone'          => $client->phone,
                'address'        => $client->address,
                'loyalty_points' => $client->loyalty_points,
            ],
            'token'   => $token,
        ], 201);
    }

    /**
     * Customer Login
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $client = Client::where('email', strtolower(trim($validated['email'])))->first();

        if (!$client || !Hash::check($validated['password'], $client->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email address or password.',
            ], 401);
        }

        $token = $client->createToken('client-web-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'client'  => [
                'id'             => $client->id,
                'name'           => $client->name,
                'email'          => $client->email,
                'phone'          => $client->phone,
                'address'        => $client->address,
                'loyalty_points' => (int) ($client->loyalty_points ?? 0),
            ],
            'token'   => $token,
        ], 200);
    }

    /**
     * Request Password Reset Link (Forgot Password)
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::broker('clients')->sendResetLink(
            ['email' => strtolower(trim($request->email))]
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'success' => true,
                'message' => 'A password reset link has been sent to your email address.',
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unable to send password reset link for this email address.',
        ], 422);
    }

    /**
     * Reset Customer Password
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token'    => ['required', 'string'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::broker('clients')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Client $client, string $password) {
                $client->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Your password has been reset successfully! You can now log in.',
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'The password reset link is invalid or has expired.',
        ], 422);
    }

    /**
     * Customer Logout (Revoke active bearer token)
     */
    public function logout(Request $request): JsonResponse
    {
        $client = $request->user();

        if ($client && $client->currentAccessToken()) {
            $client->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ], 200);
    }

    /**
     * Get Customer Profile
     */
    public function clientProfile(Request $request): JsonResponse
    {
        $client = $request->user();

        return response()->json([
            'success' => true,
            'client'  => [
                'id'             => $client->id,
                'name'           => $client->name,
                'email'          => $client->email,
                'phone'          => $client->phone,
                'address'        => $client->address,
                'loyalty_points' => (int) ($client->loyalty_points ?? 0),
            ],
        ], 200);
    }

    /**
     * Update Customer Profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $client = $request->user();

        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'phone'   => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $client->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'client'  => [
                'id'             => $client->id,
                'name'           => $client->name,
                'email'          => $client->email,
                'phone'          => $client->phone,
                'address'        => $client->address,
                'loyalty_points' => (int) ($client->loyalty_points ?? 0),
            ],
        ], 200);
    }

    /**
     * Get Customer Order History
     */
    public function clientOrders(Request $request): JsonResponse
    {
        $client = $request->user();

        $orders = Order::where('client_id', $client->id)
            ->with(['items'])
            ->latest()
            ->paginate(15);

        return response()->json($orders, 200);
    }
}