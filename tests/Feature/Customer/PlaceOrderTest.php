<?php

namespace Tests\Feature\Customer;

use App\Models\Order;
use App\Models\Product;
use App\Models\Reminder;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlaceOrderTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): User
    {
        return User::factory()->customer()->create([
            'mobile' => '09171234567',
            'house_no' => '12',
            'street' => 'Katipunan Ave',
            'city' => 'Marikina City',
        ]);
    }

    private function product(string $name, float $price, string $unit = 'gallon'): Product
    {
        return Product::create(['name' => $name, 'price' => $price, 'unit' => $unit, 'is_active' => true]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'product_id' => $this->product('5-Gallon Refill', 25.00)->id,
            'quantity' => 1,
            'order_type' => 'pickup',
            'preferred_date' => now()->addDay()->toDateString(),
            'payment_method' => 'cash',
        ], $overrides);
    }

    public function test_customer_can_place_a_refill_order(): void
    {
        $user = $this->customer();

        $response = $this->actingAs($user)->post('/customer/orders', $this->payload());

        $order = Order::first();

        $this->assertNotNull($order);
        $response->assertRedirect(route('customer.orders.show', $order));
        $this->assertSame(1, $order->items()->count());
        $this->assertSame(Order::STATUS_PENDING, $order->status);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame($user->id, $order->customer_id);
    }

    // pricing math: gallons x 25, plus 30 only for delivery
    public function test_pricing_math_matches_the_spec(): void
    {
        $user = $this->customer();
        $refill = $this->product('5-Gallon Refill', 25.00);

        $this->actingAs($user)->post('/customer/orders', $this->payload([
            'product_id' => $refill->id,
            'quantity' => 3,
            'order_type' => 'pickup',
        ]));

        $this->assertSame('75.00', Order::first()->total_amount);
    }

    public function test_delivery_adds_the_configured_fee(): void
    {
        Setting::put(Setting::DELIVERY_FEE, 30);

        $this->actingAs($this->customer())->post('/customer/orders', $this->payload([
            'quantity' => 3,
            'order_type' => 'delivery',
            'delivery_address' => '12 Katipunan Ave, Marikina City',
        ]));

        // 3 x 25 = 75, plus 30 delivery = 105
        $this->assertSame('105.00', Order::first()->total_amount);
    }

    public function test_delivery_fee_setting_is_the_source_of_truth(): void
    {
        Setting::put(Setting::DELIVERY_FEE, 45);

        $this->actingAs($this->customer())->post('/customer/orders', $this->payload([
            'quantity' => 1,
            'order_type' => 'delivery',
            'delivery_address' => '12 Katipunan Ave',
        ]));

        // 1 x 25 = 25, plus 45 = 70
        $this->assertSame('70.00', Order::first()->total_amount);
    }

    public function test_quantity_cannot_exceed_five(): void
    {
        $this->actingAs($this->customer())
            ->post('/customer/orders', $this->payload(['quantity' => 6]))
            ->assertSessionHasErrors('quantity');

        $this->assertSame(0, Order::count());
    }

    public function test_quantity_must_be_at_least_one(): void
    {
        $this->actingAs($this->customer())
            ->post('/customer/orders', $this->payload(['quantity' => 0]))
            ->assertSessionHasErrors('quantity');
    }

    public function test_delivery_orders_require_an_address(): void
    {
        $this->actingAs($this->customer())
            ->post('/customer/orders', $this->payload(['order_type' => 'delivery']))
            ->assertSessionHasErrors('delivery_address');

        $this->assertSame(0, Order::count());
    }

    public function test_pickup_orders_do_not_need_an_address(): void
    {
        $this->actingAs($this->customer())
            ->post('/customer/orders', $this->payload(['order_type' => 'pickup']))
            ->assertSessionHasNoErrors();

        $this->assertNull(Order::first()->delivery_address);
    }

    public function test_container_swap_requires_a_quantity(): void
    {
        $this->actingAs($this->customer())
            ->post('/customer/orders', $this->payload(['container_swap' => '1']))
            ->assertSessionHasErrors('container_swap_qty');

        $this->assertSame(0, Order::count());
    }

    public function test_container_swap_stores_the_quantity(): void
    {
        $this->actingAs($this->customer())->post('/customer/orders', $this->payload([
            'container_swap' => '1',
            'container_swap_qty' => 2,
        ]))->assertSessionHasNoErrors();

        $order = Order::first();

        $this->assertTrue($order->container_swap);
        $this->assertSame(2, $order->container_swap_qty);
    }

    public function test_swap_quantity_is_ignored_when_swap_is_not_selected(): void
    {
        $this->actingAs($this->customer())->post('/customer/orders', $this->payload([
            'container_swap_qty' => 3,
        ]))->assertSessionHasNoErrors();

        $this->assertFalse(Order::first()->container_swap);
        $this->assertNull(Order::first()->container_swap_qty);
    }

    public function test_preferred_date_cannot_be_in_the_past(): void
    {
        $this->actingAs($this->customer())
            ->post('/customer/orders', $this->payload(['preferred_date' => now()->subDay()->toDateString()]))
            ->assertSessionHasErrors('preferred_date');
    }

    public function test_inactive_products_cannot_be_ordered(): void
    {
        $inactive = Product::create([
            'name' => 'Retired',
            'price' => 25,
            'unit' => 'gallon',
            'is_active' => false,
        ]);

        $this->actingAs($this->customer())
            ->post('/customer/orders', $this->payload(['product_id' => $inactive->id]))
            ->assertSessionHasErrors('product_id');
    }

    public function test_weekly_reminder_opt_in_creates_a_reminder_row(): void
    {
        $user = $this->customer();

        $this->actingAs($user)->post('/customer/orders', $this->payload(['weekly_reminder' => '1']))
            ->assertSessionHasNoErrors();

        $this->assertTrue(Order::first()->weekly_reminder);
        $this->assertDatabaseHas('reminders', [
            'customer_id' => $user->id,
            'email' => $user->email,
            'frequency' => 'weekly',
            'is_active' => true,
        ]);
    }

    public function test_opting_out_deactivates_an_existing_reminder(): void
    {
        $user = $this->customer();
        Reminder::create([
            'customer_id' => $user->id,
            'email' => $user->email,
            'frequency' => 'weekly',
            'is_active' => true,
        ]);

        $this->actingAs($user)->post('/customer/orders', $this->payload());

        $this->assertFalse(Reminder::where('customer_id', $user->id)->value('is_active'));
    }

    public function test_no_reminder_row_is_created_without_opt_in(): void
    {
        $user = $this->customer();

        $this->actingAs($user)->post('/customer/orders', $this->payload());

        $this->assertSame(0, Reminder::where('customer_id', $user->id)->count());
    }
}
