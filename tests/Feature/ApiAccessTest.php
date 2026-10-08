<?php

namespace Tests\Feature;

use App\Models\ContainerInventory;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\InventorySeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InventorySeeder::class);
        $this->seed(ProductSeeder::class);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        foreach (['/api/orders', '/api/containers', '/api/inventory', '/api/notifications/low-stock'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }

        $this->postJson('/api/orders', [])->assertUnauthorized();
    }

    public function test_staff_and_admin_can_read_orders(): void
    {
        Order::factory()->create();

        foreach ([User::ROLE_STAFF, User::ROLE_ADMIN] as $role) {
            $this->actingAs(User::factory()->role($role)->create())
                ->getJson('/api/orders')
                ->assertOk()
                ->assertJsonStructure(['data']);
        }
    }

    // a customer must never read the station-wide order feed
    public function test_customers_cannot_read_the_order_feed(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->getJson('/api/orders')
            ->assertForbidden();
    }

    public function test_customers_cannot_read_inventory_or_low_stock(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->getJson('/api/inventory')->assertForbidden();
        $this->actingAs($customer)->getJson('/api/notifications/low-stock')->assertForbidden();
    }

    public function test_drivers_can_read_containers_but_not_inventory(): void
    {
        $driver = User::factory()->driver()->create();

        $this->actingAs($driver)->getJson('/api/containers')->assertOk();
        $this->actingAs($driver)->getJson('/api/inventory')->assertForbidden();
        $this->actingAs($driver)->getJson('/api/orders')->assertForbidden();
    }

    public function test_containers_endpoint_returns_counts_and_fill_ratio(): void
    {
        // seeded 40 full of 65 at station
        $this->actingAs(User::factory()->staff()->create())
            ->getJson('/api/containers')
            ->assertOk()
            ->assertJsonPath('full', 40)
            ->assertJsonPath('empty', 25)
            ->assertJsonPath('at_station', 65)
            ->assertJsonPath('fill_ratio', 0.6154);
    }

    public function test_low_stock_endpoint_lists_only_low_items(): void
    {
        InventoryItem::create(['name' => 'healthy', 'quantity' => 99, 'threshold' => 5]);

        $response = $this->actingAs(User::factory()->staff()->create())
            ->getJson('/api/notifications/low-stock');

        $response->assertOk()->assertJsonPath('count', 1);

        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('dispenser_maintenance'));
        $this->assertFalse($names->contains('healthy'));
    }

    public function test_inventory_endpoint_flags_low_items(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())
            ->getJson('/api/inventory');

        $response->assertOk();

        $low = collect($response->json('data'))->where('low', true)->pluck('name');
        $this->assertTrue($low->contains('dispenser_maintenance'));
    }

    public function test_customer_can_place_an_order_via_api(): void
    {
        $customer = User::factory()->customer()->create(['barangay' => 'Malanday']);
        $product = Product::where('unit', 'gallon')->first();

        $response = $this->actingAs($customer)->postJson('/api/orders', [
            'product_id' => $product->id,
            'quantity' => 3,
            'order_type' => 'pickup',
            'preferred_date' => now()->addDay()->toDateString(),
            'payment_method' => 'cash',
        ]);

        $response->assertCreated()->assertJsonStructure(['order_id', 'redirect', 'total_amount']);

        // 3 x 25, no delivery fee
        $this->assertEqualsWithDelta(75.0, (float) $response->json('total_amount'), 0.001);
        $this->assertDatabaseHas('orders', ['customer_id' => $customer->id, 'total_amount' => 75]);
    }

    public function test_api_order_validation_returns_422_with_errors(): void
    {
        $customer = User::factory()->customer()->create();
        $product = Product::where('unit', 'gallon')->first();

        $this->actingAs($customer)->postJson('/api/orders', [
            'product_id' => $product->id,
            'quantity' => 9,
            'order_type' => 'pickup',
            'preferred_date' => now()->addDay()->toDateString(),
            'payment_method' => 'cash',
        ])->assertStatus(422)->assertJsonValidationErrors('quantity');
    }

    // only customers may place orders through the api
    public function test_internal_users_cannot_place_orders_via_api(): void
    {
        $product = Product::where('unit', 'gallon')->first();

        foreach ([User::ROLE_STAFF, User::ROLE_ADMIN, User::ROLE_DRIVER] as $role) {
            $this->actingAs(User::factory()->role($role)->create())
                ->postJson('/api/orders', [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'order_type' => 'pickup',
                    'preferred_date' => now()->addDay()->toDateString(),
                    'payment_method' => 'cash',
                ])->assertForbidden();
        }

        $this->assertSame(0, Order::count());
    }

    public function test_api_and_web_paths_create_the_same_order(): void
    {
        $product = Product::where('unit', 'gallon')->first();
        $payload = [
            'product_id' => $product->id,
            'quantity' => 2,
            'order_type' => 'pickup',
            'preferred_date' => now()->addDay()->toDateString(),
            'payment_method' => 'cash',
        ];

        $viaApi = User::factory()->customer()->create();
        $this->actingAs($viaApi)->postJson('/api/orders', $payload)->assertCreated();

        $viaWeb = User::factory()->customer()->create();
        $this->actingAs($viaWeb)->post('/customer/orders', $payload)->assertRedirect();

        $apiOrder = Order::where('customer_id', $viaApi->id)->first();
        $webOrder = Order::where('customer_id', $viaWeb->id)->first();

        $this->assertSame((float) $webOrder->total_amount, (float) $apiOrder->total_amount);
        $this->assertSame($webOrder->items()->count(), $apiOrder->items()->count());
    }

    public function test_container_counts_reflect_fills(): void
    {
        ContainerInventory::where('status', ContainerInventory::AT_STATION_FULL)->update(['quantity' => 5]);
        ContainerInventory::where('status', ContainerInventory::AT_STATION_EMPTY)->update(['quantity' => 5]);

        // 50 percent
        $this->actingAs(User::factory()->staff()->create())
            ->getJson('/api/containers')
            ->assertJsonPath('fill_ratio', 0.5);
    }
}
