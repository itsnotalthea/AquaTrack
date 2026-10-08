<?php

namespace Tests\Feature;

use App\Models\Delivery;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    // deliveries.order_id does not cascade, so deleting an account that has
    // orders would otherwise fail with a raw foreign key error
    public function test_customer_with_a_delivery_cannot_delete_their_account(): void
    {
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->create(['customer_id' => $customer->id, 'order_type' => 'delivery']);
        Delivery::create(['order_id' => $order->id, 'driver_id' => User::factory()->driver()->create()->id]);

        $response = $this->actingAs($customer)->delete('/profile', ['password' => 'password']);

        $response->assertRedirect('/profile');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['id' => $customer->id]);
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    public function test_customer_with_pickup_orders_cannot_delete_their_account(): void
    {
        $customer = User::factory()->customer()->create();
        Order::factory()->create(['customer_id' => $customer->id, 'order_type' => 'pickup']);

        $this->actingAs($customer)
            ->delete('/profile', ['password' => 'password'])
            ->assertRedirect('/profile');

        $this->assertAuthenticated();
    }

    public function test_customer_without_orders_can_delete_their_account(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->delete('/profile', ['password' => 'password'])
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $customer->id]);
    }
}
