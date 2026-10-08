<?php

namespace Tests\Feature\Customer;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_reach_the_order_pages(): void
    {
        $this->get('/customer/orders')->assertRedirect('/login');
        $this->get('/customer/orders/create')->assertRedirect('/login');
        $this->get('/customer/orders/1')->assertRedirect('/login');
    }

    public function test_ordering_requires_login_and_is_blocked_for_staff(): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->get('/customer/orders/create')
            ->assertForbidden();
    }

    public function test_customer_sees_only_their_own_orders(): void
    {
        $mine = User::factory()->customer()->create();
        $theirs = User::factory()->customer()->create();

        $mineOrder = Order::factory()->create(['customer_id' => $mine->id]);
        Order::factory()->create(['customer_id' => $theirs->id]);

        $response = $this->actingAs($mine)->get('/customer/orders');

        $response->assertOk()->assertSee($mineOrder->id);
        $this->assertSame(1, $response->viewData('orders')->total());
    }

    public function test_customer_cannot_open_another_customers_order(): void
    {
        $mine = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();
        $theirs = Order::factory()->create(['customer_id' => $other->id]);

        $this->actingAs($mine)
            ->get("/customer/orders/{$theirs->id}")
            ->assertNotFound();
    }

    public function test_customer_can_view_their_own_order_detail(): void
    {
        $user = User::factory()->customer()->create();
        $order = Order::factory()->create(['customer_id' => $user->id]);

        $this->actingAs($user)
            ->get("/customer/orders/{$order->id}")
            ->assertOk()
            ->assertSee('Order #'.$order->id);
    }

    public function test_create_page_lists_only_active_products(): void
    {
        Product::create(['name' => 'Active Refill', 'price' => 25, 'unit' => 'gallon', 'is_active' => true]);
        Product::create(['name' => 'Retired Refill', 'price' => 30, 'unit' => 'gallon', 'is_active' => false]);

        $this->actingAs(User::factory()->customer()->create())
            ->get('/customer/orders/create')
            ->assertOk()
            ->assertSee('Active Refill')
            ->assertDontSee('Retired Refill');
    }
}
