<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
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
    }

    public function register(Request $request)
    {
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
    }

    public function clientOrders(Request $request)
    {
        $client = $request->user(); // Returns current Client

        $orders = Order::where('client_id', $client->id)
            ->with('items') // assuming items relationship exists
            ->latest()
            ->get();

        return response()->json($orders);
    }


    public function clientProfile(Request $request)
    {
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
    }
}
