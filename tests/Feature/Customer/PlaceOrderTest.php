<?php

namespace Tests\Feature\Customer;

use App\Models\Order;
use App\Models\Product;
use App\Models\Reminder;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_customer_can_order_more_than_five_of_a_product(): void
    {
        $this->actingAs($this->customer())
            ->post('/customer/orders', $this->payload(['quantity' => 6]))
            ->assertSessionHasNoErrors();

        $this->assertSame(6, Order::first()->items()->value('quantity'));
    }

    public function test_cart_accepts_more_than_five_of_a_product(): void
    {
        $product = $this->product('5-Gallon Refill', 25.00);

        $this->actingAs($this->customer())
            ->postJson('/customer/cart', [
                'product_id' => $product->id,
                'quantity' => 6,
                'container_mode' => 'exchange',
            ])
            ->assertOk()
            ->assertJsonPath('count', 6)
            ->assertJsonPath('items.0.quantity', 6);
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

    public function test_gcash_and_maya_orders_require_a_reference_number(): void
    {
        $this->actingAs($this->customer())
            ->post('/customer/orders', $this->payload(['payment_method' => 'gcash']))
            ->assertSessionHasErrors('payment_reference');

        $this->actingAs($this->customer())
            ->post('/customer/orders', $this->payload(['payment_method' => 'maya']))
            ->assertSessionHasErrors('payment_reference');

        $this->assertSame(0, Order::count());
    }

    public function test_gcash_order_stores_the_reference_number(): void
    {
        $this->actingAs($this->customer())->post('/customer/orders', $this->payload([
            'payment_method' => 'gcash',
            'payment_reference' => 'GC-123456789',
        ]))->assertSessionHasNoErrors();

        $order = Order::first();

        $this->assertSame('gcash', $order->payment_method);
        $this->assertSame('GC-123456789', $order->payment_reference);
        $this->assertNull($order->payment_proof_path);
    }

    public function test_optional_payment_proof_is_stored_for_ewallet_orders(): void
    {
        Storage::fake('public');

        $path = tempnam(sys_get_temp_dir(), 'proof');
        file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));

        $proof = new UploadedFile($path, 'receipt.png', 'image/png', null, true);

        $this->actingAs($this->customer())->post('/customer/orders', $this->payload([
            'payment_method' => 'maya',
            'payment_reference' => 'MY-987654321',
            'payment_proof' => $proof,
        ]))->assertSessionHasNoErrors();

        $order = Order::first();

        $this->assertNotNull($order->payment_proof_path);
        Storage::disk('public')->assertExists($order->payment_proof_path);
    }

    public function test_cash_orders_do_not_require_a_reference_number(): void
    {
        $this->actingAs($this->customer())->post('/customer/orders', $this->payload([
            'payment_method' => 'cash',
        ]))->assertSessionHasNoErrors();

        $this->assertNull(Order::first()->payment_reference);
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

    public function test_customer_can_edit_a_pending_order_and_the_changes_are_logged(): void
    {
        $customer = $this->customer();
        $product = $this->product('5-Gallon Refill', 25.00);
        $this->actingAs($customer)->post('/customer/orders', $this->payload([
            'product_id' => $product->id,
            'quantity' => 2,
            'delivery_address' => 'Old address',
            'order_type' => 'delivery',
        ]))->assertSessionHasNoErrors();
        $order = Order::firstOrFail();

        $this->actingAs($customer)->get("/customer/orders/{$order->id}/edit")
            ->assertOk()
            ->assertSee('Edit order #'.$order->id);

        $this->actingAs($customer)->put("/customer/orders/{$order->id}", [
            'items' => [[
                'product_id' => $product->id,
                'quantity' => 7,
                'container_mode' => 'bring_your_own',
            ]],
            'order_type' => 'delivery',
            'delivery_address' => 'Updated address',
            'barangay' => 'Barangka',
            'preferred_date' => now()->addDays(3)->toDateString(),
            'notes' => 'Please call on arrival.',
        ])->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame('Updated address', $order->delivery_address);
        $this->assertSame('Please call on arrival.', $order->notes);
        $this->assertSame(7, $order->items()->value('quantity'));
        $this->assertSame('175.00', $order->subtotal);
        $this->assertDatabaseHas('order_change_logs', [
            'order_id' => $order->id,
            'user_id' => $customer->id,
        ]);
        $this->assertSame('Updated address', $order->changeLogs()->latest()->first()->changes['Delivery address']['new']);
    }

    public function test_customer_cannot_edit_an_order_after_it_is_confirmed(): void
    {
        $customer = $this->customer();
        $this->actingAs($customer)->post('/customer/orders', $this->payload())->assertSessionHasNoErrors();
        $order = Order::firstOrFail();
        $order->update(['status' => Order::STATUS_CONFIRMED]);

        $this->actingAs($customer)->get("/customer/orders/{$order->id}/edit")->assertForbidden();
        $this->actingAs($customer)->put("/customer/orders/{$order->id}", [
            'items' => [[
                'product_id' => $order->items()->value('product_id'),
                'quantity' => 2,
            ]],
            'order_type' => 'pickup',
            'preferred_date' => now()->addDay()->toDateString(),
        ])->assertForbidden();
    }

    public function test_customer_cannot_edit_another_customers_order(): void
    {
        $owner = $this->customer();
        $otherCustomer = $this->customer();
        $this->actingAs($owner)->post('/customer/orders', $this->payload())->assertSessionHasNoErrors();
        $order = Order::firstOrFail();

        $this->actingAs($otherCustomer)->get("/customer/orders/{$order->id}/edit")->assertNotFound();
        $this->actingAs($otherCustomer)->put("/customer/orders/{$order->id}", [])->assertNotFound();
    }
}
