<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;

class OrderPricing
{
    public const MAX_QUANTITY = 5;

    // delivery fee only applies to delivery orders
    public static function deliveryFee(): float
    {
        return Setting::deliveryFee();
    }

    public static function subtotal(float $unitPrice, int $quantity): float
    {
        return round($unitPrice * $quantity, 2);
    }

    public static function total(float $unitPrice, int $quantity, string $orderType): float
    {
        $total = self::subtotal($unitPrice, $quantity);

        if ($orderType === Order::TYPE_DELIVERY) {
            $total += self::deliveryFee();
        }

        return round($total, 2);
    }
}
