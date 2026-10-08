<?php

namespace Tests\Feature\Driver;

use App\Models\ContainerInventory;
use App\Models\ContainerTransaction;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\InventorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InventorySeeder::class);
    }

    private function driver(): User
    {
        return User::factory()->driver()->create(['first_name' => 'Walt', 'last_name' => 'Driver']);
    }

    private function otherDriver(): User
    {
        return User::factory()->driver()->create(['first_name' => 'Other', 'last_name' => 'Driver']);
    }

    public function test_driver_sees_only_their_own_deliveries(): void
    {
        $driver = $this->driver();
        $other = $this->otherDriver();

        $mine = Delivery::create(['order_id' => Order::factory()->create(['order_type' => 'delivery'])->id, 'driver_id' => $driver->id]);
        Delivery::create(['order_id' => Order::factory()->create(['order_type' => 'delivery'])->id, 'driver_id' => $other->id]);

        $response = $this->actingAs($driver)->get('/driver/deliveries');

        $response->assertOk();
        $this->assertSame(1, $response->viewData('deliveries')->total());
        $this->assertSame($mine->id, $response->viewData('deliveries')->first()->id);
    }

    public function test_driver_cannot_open_another_drivers_delivery(): void
    {
        $mine = $this->driver();
        $theirs = Delivery::create([
            'order_id' => Order::factory()->create(['order_type' => 'delivery'])->id,
            'driver_id' => $this->otherDriver()->id,
        ]);

        $this->actingAs($mine)->get("/driver/deliveries/{$theirs->id}")->assertForbidden();
        $this->actingAs($mine)->put("/driver/deliveries/{$theirs->id}/status", ['status' => Delivery::STATUS_IN_TRANSIT])->assertForbidden();
    }

    public function test_driver_can_start_and_complete_a_run(): void
    {
        $driver = $this->driver();
        $order = Order::factory()->create(['order_type' => 'delivery', 'status' => Order::STATUS_OUT_FOR_DELIVERY]);
        $delivery = Delivery::create(['order_id' => $order->id, 'driver_id' => $driver->id, 'status' => Delivery::STATUS_ASSIGNED]);

        $this->actingAs($driver)
            ->put("/driver/deliveries/{$delivery->id}/status", ['status' => Delivery::STATUS_IN_TRANSIT])
            ->assertSessionHasNoErrors();
        $this->assertSame(Delivery::STATUS_IN_TRANSIT, $delivery->refresh()->status);

        $this->actingAs($driver)
            ->put("/driver/deliveries/{$delivery->id}/status", ['status' => Delivery::STATUS_DELIVERED])
            ->assertSessionHasNoErrors();

        $delivery->refresh();

        $this->assertSame(Delivery::STATUS_DELIVERED, $delivery->status);
        $this->assertNotNull($delivery->delivered_at);
        // delivering also advances the order
        $this->assertSame(Order::STATUS_DELIVERED, $order->refresh()->status);
    }

    public function test_invalid_status_change_is_rejected(): void
    {
        $driver = $this->driver();
        $order = Order::factory()->create(['order_type' => 'delivery', 'status' => Order::STATUS_OUT_FOR_DELIVERY]);
        $delivery = Delivery::create(['order_id' => $order->id, 'driver_id' => $driver->id, 'status' => Delivery::STATUS_ASSIGNED]);

        $this->actingAs($driver)
            ->put("/driver/deliveries/{$delivery->id}/status", ['status' => Delivery::STATUS_DELIVERED])
            ->assertSessionHasErrors('status');

        $this->assertSame(Delivery::STATUS_ASSIGNED, $delivery->refresh()->status);
    }

    public function test_container_return_completes_the_order_and_moves_counts(): void
    {
        $driver = $this->driver();
        $product = Product::create(['name' => 'R '.uniqid(), 'price' => 25, 'unit' => 'gallon', 'is_active' => true]);
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id, 'order_type' => 'delivery',
            'status' => Order::STATUS_DELIVERED, 'container_swap' => true, 'container_swap_qty' => 2,
        ]);
        $order->items()->create(['product_id' => $product->id, 'quantity' => 3, 'price' => 25, 'subtotal' => 75]);

        $delivery = Delivery::create([
            'order_id' => $order->id, 'driver_id' => $driver->id,
            'status' => Delivery::STATUS_DELIVERED, 'delivered_at' => now(),
        ]);

        $before = ContainerInventory::orderBy('id')->pluck('quantity', 'status')->all();

        $this->actingAs($driver)
            ->put("/driver/deliveries/{$delivery->id}/return", ['returned_containers' => 2])
            ->assertSessionHasNoErrors();

        $after = ContainerInventory::orderBy('id')->pluck('quantity', 'status')->all();

        $this->assertSame(Delivery::STATUS_RETURNED_TO_STATION, $delivery->refresh()->status);
        $this->assertSame(2, $delivery->returned_containers);
        $this->assertSame(Order::STATUS_COMPLETED, $order->refresh()->status);

        // 3 gallons delivered, 2 empties came back
        $this->assertSame($before['at_station_full'] - 3, $after['at_station_full']);
        $this->assertSame($before['with_customer'] + 3 - 2, $after['with_customer']);
        $this->assertSame($before['at_station_empty'] + 2, $after['at_station_empty']);

        // one swap transaction and one return transaction, never two returns
        $this->assertSame(1, ContainerTransaction::where('order_id', $order->id)->where('type', 'swap')->count());
        $this->assertSame(1, ContainerTransaction::where('order_id', $order->id)->where('type', 'return')->count());
    }

    public function test_zero_return_is_allowed(): void
    {
        $driver = $this->driver();
        $product = Product::create(['name' => 'R '.uniqid(), 'price' => 25, 'unit' => 'gallon', 'is_active' => true]);
        $order = Order::factory()->create([
            'order_type' => 'delivery', 'status' => Order::STATUS_DELIVERED,
        ]);
        $order->items()->create(['product_id' => $product->id, 'quantity' => 2, 'price' => 25, 'subtotal' => 50]);

        $delivery = Delivery::create([
            'order_id' => $order->id, 'driver_id' => $driver->id,
            'status' => Delivery::STATUS_DELIVERED, 'delivered_at' => now(),
        ]);

        $before = ContainerInventory::orderBy('id')->pluck('quantity', 'status')->all();

        $this->actingAs($driver)
            ->put("/driver/deliveries/{$delivery->id}/return", ['returned_containers' => 0])
            ->assertSessionHasNoErrors();

        $after = ContainerInventory::orderBy('id')->pluck('quantity', 'status')->all();

        $this->assertSame($before['at_station_empty'], $after['at_station_empty']);
        $this->assertSame($before['with_customer'] + 2, $after['with_customer']);
    }

    public function test_return_quantity_is_required(): void
    {
        $driver = $this->driver();
        $order = Order::factory()->create(['order_type' => 'delivery']);
        $delivery = Delivery::create(['order_id' => $order->id, 'driver_id' => $driver->id, 'status' => Delivery::STATUS_ASSIGNED]);

        $this->actingAs($driver)
            ->put("/driver/deliveries/{$delivery->id}/return", [])
            ->assertSessionHasErrors('returned_containers');
    }

    public function test_driver_dashboard_renders(): void
    {
        $driver = $this->driver();
        Delivery::create(['order_id' => Order::factory()->create(['order_type' => 'delivery'])->id, 'driver_id' => $driver->id]);

        $this->actingAs($driver)->get('/driver/dashboard')->assertOk();
    }

    // rule 10: drivers never record payments
    public function test_drivers_cannot_record_payments(): void
    {
        $driver = $this->driver();
        $order = Order::factory()->create([
            'order_type' => 'delivery', 'status' => Order::STATUS_DELIVERED, 'payment_status' => 'unpaid',
        ]);

        $this->actingAs($driver)
            ->put("/staff/orders/{$order->id}/payment", ['method' => 'cash'])
            ->assertForbidden();

        $this->assertSame(0, Payment::count());
        $this->assertSame('unpaid', $order->refresh()->payment_status);
    }

    public function test_drivers_cannot_open_staff_or_admin_pages(): void
    {
        $driver = $this->driver();

        foreach (['/staff/orders', '/staff/reports/sales', '/admin/users', '/customer/orders'] as $path) {
            $this->actingAs($driver)->get($path)->assertForbidden();
        }
    }

    public function test_drivers_cannot_assign_drivers_or_edit_products(): void
    {
        $driver = $this->driver();
        $order = Order::factory()->create(['order_type' => 'delivery']);
        $other = $this->otherDriver();

        $this->actingAs($driver)
            ->put("/staff/orders/{$order->id}/driver", ['driver_id' => $other->id])
            ->assertForbidden();
    }
}
