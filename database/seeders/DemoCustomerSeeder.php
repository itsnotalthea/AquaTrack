<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DemoCustomerSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::active()->get()->keyBy('unit');
        $requiredUnits = [Product::UNIT_GALLON, Product::UNIT_PIECE, Product::UNIT_MONTH];

        foreach ($requiredUnits as $unit) {
            if (! $products->has($unit)) {
                throw new RuntimeException("An active product for unit [{$unit}] is required to seed demo orders.");
            }
        }

        $customers = [
            [
                'first_name' => 'John',
                'last_name' => 'Pork',
                'email' => 'john.pork@example.com',
                'mobile' => '09170000001',
                'house_no' => '12',
                'street' => 'Katipunan Avenue',
                'barangay' => 'Concepcion Uno',
                'orders' => [
                    ['type' => Order::TYPE_DELIVERY, 'method' => 'cash', 'items' => [[Product::UNIT_GALLON, 2, Product::CONTAINER_EXCHANGE]]],
                    ['type' => Order::TYPE_PICKUP, 'method' => 'gcash', 'items' => [[Product::UNIT_PIECE, 1, null], [Product::UNIT_GALLON, 3, Product::CONTAINER_BRING_YOUR_OWN]]],
                    ['type' => Order::TYPE_DELIVERY, 'method' => 'maya', 'items' => [[Product::UNIT_MONTH, 1, null], [Product::UNIT_GALLON, 4, Product::CONTAINER_EXCHANGE]]],
                    ['type' => Order::TYPE_PICKUP, 'method' => 'cash', 'items' => [[Product::UNIT_PIECE, 2, null], [Product::UNIT_GALLON, 1, Product::CONTAINER_BRING_YOUR_OWN]]],
                ],
            ],
            [
                'first_name' => 'Quandale',
                'last_name' => 'Dingle',
                'email' => 'quandale.dingle@example.com',
                'mobile' => '09170000002',
                'house_no' => '24',
                'street' => 'J. P. Rizal Street',
                'barangay' => 'Malanday',
                'orders' => [
                    ['type' => Order::TYPE_PICKUP, 'method' => 'cash', 'items' => [[Product::UNIT_GALLON, 4, Product::CONTAINER_EXCHANGE]]],
                    ['type' => Order::TYPE_DELIVERY, 'method' => 'gcash', 'items' => [[Product::UNIT_MONTH, 1, null], [Product::UNIT_GALLON, 2, Product::CONTAINER_BRING_YOUR_OWN]]],
                    ['type' => Order::TYPE_PICKUP, 'method' => 'maya', 'items' => [[Product::UNIT_PIECE, 3, null]]],
                    ['type' => Order::TYPE_DELIVERY, 'method' => 'cash', 'items' => [[Product::UNIT_PIECE, 1, null], [Product::UNIT_GALLON, 5, Product::CONTAINER_EXCHANGE]]],
                ],
            ],
        ];

        $deliveryFee = Setting::deliveryFee();

        foreach ($customers as $customerData) {
            $user = User::updateOrCreate(
                ['email' => $customerData['email']],
                [
                    'first_name' => $customerData['first_name'],
                    'last_name' => $customerData['last_name'],
                    'password' => Hash::make('password'),
                    'role' => User::ROLE_CUSTOMER,
                    'mobile' => $customerData['mobile'],
                    'house_no' => $customerData['house_no'],
                    'street' => $customerData['street'],
                    'barangay' => $customerData['barangay'],
                    'city' => 'Marikina City',
                ]
            );

            foreach ($customerData['orders'] as $index => $orderData) {
                $subtotal = 0;
                $containerSwapQuantity = 0;
                $lines = [];

                foreach ($orderData['items'] as [$unit, $quantity, $containerMode]) {
                    $product = $products->get($unit);
                    $lineSubtotal = (float) $product->price * $quantity;
                    $subtotal += $lineSubtotal;

                    if ($product->isRefill() && $containerMode === Product::CONTAINER_EXCHANGE) {
                        $containerSwapQuantity += $quantity;
                    }

                    $lines[] = [
                        'product' => $product,
                        'quantity' => $quantity,
                        'container_mode' => $containerMode,
                        'subtotal' => $lineSubtotal,
                    ];
                }

                $note = 'Demo sample order '.($index + 1);
                $orderDeliveryFee = $orderData['type'] === Order::TYPE_DELIVERY ? $deliveryFee : 0;
                $order = Order::updateOrCreate(
                    ['customer_id' => $user->id, 'notes' => $note],
                    [
                        'order_type' => $orderData['type'],
                        'status' => Order::STATUS_PENDING,
                        'subtotal' => $subtotal,
                        'delivery_fee' => $orderDeliveryFee,
                        'total_amount' => $subtotal + $orderDeliveryFee,
                        'payment_status' => 'unpaid',
                        'payment_method' => $orderData['method'],
                        'delivery_address' => $orderData['type'] === Order::TYPE_DELIVERY ? $user->full_address : null,
                        'barangay' => $user->barangay,
                        'preferred_date' => today()->addDays($index + 2),
                        'container_swap' => $containerSwapQuantity > 0,
                        'container_swap_qty' => $containerSwapQuantity,
                        'returned_containers' => 0,
                        'weekly_reminder' => false,
                    ]
                );

                foreach ($lines as $line) {
                    $product = $line['product'];
                    OrderItem::updateOrCreate(
                        ['order_id' => $order->id, 'product_id' => $product->id],
                        [
                            'product_name' => $product->name,
                            'unit' => $product->unit,
                            'fulfillment_type' => $product->resolvedFulfillmentType(),
                            'inventory_item_id' => $product->inventory_item_id,
                            'container_mode' => $line['container_mode'],
                            'quantity' => $line['quantity'],
                            'price' => $product->price,
                            'subtotal' => $line['subtotal'],
                        ]
                    );
                }
            }
        }
    }
}