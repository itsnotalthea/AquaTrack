<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = fake()->numberBetween(1, 5) * 25.00;

        return [
            'customer_id' => User::factory()->customer(),
            'order_type' => fake()->randomElement([Order::TYPE_DELIVERY, Order::TYPE_PICKUP]),
            'status' => Order::STATUS_PENDING,
            'total_amount' => $subtotal,
            'payment_status' => 'unpaid',
            'payment_method' => fake()->randomElement(['cash', 'gcash', 'maya']),
            'delivery_address' => '12 Katipunan Ave, Marikina City',
            'preferred_date' => now()->addDay(),
            'notes' => null,
            'container_swap' => false,
            'weekly_reminder' => false,
        ];
    }

    public function delivery(): static
    {
        return $this->state(fn (array $attributes) => ['order_type' => Order::TYPE_DELIVERY]);
    }

    public function pickup(): static
    {
        return $this->state(fn (array $attributes) => ['order_type' => Order::TYPE_PICKUP]);
    }

    public function status(string $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => ['payment_status' => 'paid']);
    }
}
