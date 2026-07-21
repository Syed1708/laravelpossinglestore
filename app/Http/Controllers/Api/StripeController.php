<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Terminal\ConnectionToken;

class StripeController extends Controller
{
    /**
     * Generate a temporary Connection Token to authorize your physical card readers.
     */
    public function getConnectionToken()
    {
        // Fetch your Stripe Secret Key securely from your .env
        Stripe::setApiKey(config('services.stripe.secret') ?? env('STRIPE_SECRET'));

        try {
            // Create a secure connection token for Stripe Terminal SDK
            $token = ConnectionToken::create();
            
            return response()->json([
                'secret' => $token->secret
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}