<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Order;
use App\Models\LoyaltyTransaction;

class LoyaltyService
{
    /**
     * 100 Loyalty Points = €5.00 Discount (1 Point = €0.05)
     */
    public const POINT_REDEMPTION_VALUE = 0.05;

    /**
     * Award loyalty points when an order is completed/paid (1 Point per €1 spent)
     */
    public static function awardPointsForOrder(Order $order): void
    {
        if (!$order->client_id || $order->points_earned > 0) {
            return; // Guest checkout or already awarded
        }

        $client = Client::find($order->client_id);
        if (!$client) return;

        // Earn 1 Point per €1 spent (excl. discounts)
        $earnedPoints = (int) floor($order->total_incl_vat);

        if ($earnedPoints <= 0) return;

        $client->increment('loyalty_points', $earnedPoints);
        $order->update(['points_earned' => $earnedPoints]);

        LoyaltyTransaction::create([
            'client_id'   => $client->id,
            'order_id'    => $order->id,
            'type'        => 'earned',
            'points'      => $earnedPoints,
            'description' => "Earned {$earnedPoints} points from Order #{$order->sequence_number}",
        ]);
    }

    /**
     * Redeem customer points for checkout discount
     */
    public static function redeemPoints(Client $client, int $pointsToRedeem, Order $order): float
    {
        if ($pointsToRedeem <= 0 || $client->loyalty_points < $pointsToRedeem) {
            return 0.00;
        }

        $discountAmount = round($pointsToRedeem * self::POINT_REDEMPTION_VALUE, 2);

        $client->decrement('loyalty_points', $pointsToRedeem);
        $order->update(['points_redeemed' => $pointsToRedeem]);

        LoyaltyTransaction::create([
            'client_id'   => $client->id,
            'order_id'    => $order->id,
            'type'        => 'redeemed',
            'points'      => -$pointsToRedeem,
            'description' => "Redeemed {$pointsToRedeem} points for €{$discountAmount} discount on Order #{$order->sequence_number}",
        ]);

        return $discountAmount;
    }
}