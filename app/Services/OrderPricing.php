<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;

class OrderPricing
{
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

    /**
     * @param  array<int, array{price: float|string, quantity: int}>  $lines
     * @return array{subtotal: float, delivery_fee: float, total: float}
     */
    public static function totals(array $lines, string $orderType): array
    {
        $subtotal = 0.0;

        foreach ($lines as $line) {
            $subtotal += self::subtotal((float) $line['price'], (int) $line['quantity']);
        }

        $subtotal = round($subtotal, 2);
        $deliveryFee = $orderType === Order::TYPE_DELIVERY ? self::deliveryFee() : 0.0;

        return [
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'total' => round($subtotal + $deliveryFee, 2),
        ];
    }
}
