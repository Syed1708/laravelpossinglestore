<?php

namespace App\Http\Controllers\Api\v1\staff;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    /**
     * Staff Login (Cashiers, Managers, Admins)
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', strtolower(trim($validated['email'])))->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid staff credentials.',
            ], 401);
        }

        $token = $user->createToken('staff-pos-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Staff login successful.',
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'token'   => $token,
        ], 200);
    }

    /**
     * Staff Registration (Back-office onboarding)
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', PasswordRule::min(8)],
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => strtolower(trim($validated['email'])),
            'password' => Hash::make($validated['password']),
        ]);

        $token = $user->createToken('staff-pos-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Staff user created successfully.',
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'token'   => $token,
        ], 201);
    }
}