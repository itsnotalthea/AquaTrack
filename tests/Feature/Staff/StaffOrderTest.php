<?php

namespace Tests\Feature\Staff;

use App\Models\ContainerInventory;
use App\Models\ContainerTransaction;
use App\Models\Delivery;
use App\Models\InventoryItem;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\InventorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffOrderTest extends TestCase
{
    use RefreshDatabase;

    // container and stock rows are reference data the fulfillment service needs
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InventorySeeder::class);
    }

    private function staff(): User
    {
        return User::factory()->staff()->create();
    }

    private function driver(): User
    {
        return User::factory()->driver()->create();
    }

    private function order(string $type = 'pickup', string $status = Order::STATUS_PENDING): Order
    {
        $product = Product::create([
            'name' => 'Refill '.uniqid(),
            'price' => 25,
            'unit' => 'gallon',
            'is_active' => true,
        ]);

        $order = Order::factory()->create(['order_type' => $type, 'status' => $status, 'total_amount' => 75]);

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 3,
            'price' => 25,
            'subtotal' => 75,
        ]);

        return $order->fresh();
    }

    public function test_staff_sees_the_orders_list(): void
    {
        $this->actingAs($this->staff())->get('/staff/orders')->assertOk();
    }

    public function test_customers_and_drivers_cannot_reach_staff_pages(): void
    {
        foreach ([User::ROLE_CUSTOMER, User::ROLE_DRIVER] as $role) {
            $this->actingAs(User::factory()->role($role)->create())
                ->get('/staff/orders')->assertForbidden();
        }
    }

    public function test_staff_can_advance_a_pickup_order_through_its_flow(): void
    {
        $order = $this->order('pickup');
        $staff = $this->staff();

        foreach ([Order::STATUS_CONFIRMED, Order::STATUS_PREPARING, Order::STATUS_READY_FOR_PICKUP] as $next) {
            $this->actingAs($staff)
                ->put("/staff/orders/{$order->id}/status", ['status' => $next])
                ->assertSessionHasNoErrors();

            $this->assertSame($next, $order->refresh()->status);
        }
    }

    public function test_invalid_transition_is_rejected(): void
    {
        $order = $this->order('pickup', Order::STATUS_PENDING);

        $this->actingAs($this->staff())
            ->put("/staff/orders/{$order->id}/status", ['status' => Order::STATUS_COMPLETED])
            ->assertSessionHasErrors('status');

        $this->assertSame(Order::STATUS_PENDING, $order->refresh()->status);
    }

    public function test_delivery_order_cannot_go_out_without_a_driver(): void
    {
        $order = $this->order('delivery', Order::STATUS_PREPARING);

        $this->actingAs($this->staff())
            ->put("/staff/orders/{$order->id}/status", ['status' => Order::STATUS_OUT_FOR_DELIVERY])
            ->assertSessionHasErrors('status');

        $this->assertSame(Order::STATUS_PREPARING, $order->refresh()->status);
    }

    public function test_staff_can_assign_a_driver_and_dispatch(): void
    {
        $order = $this->order('delivery', Order::STATUS_PREPARING);
        $driver = $this->driver();
        $staff = $this->staff();

        $this->actingAs($staff)
            ->put("/staff/orders/{$order->id}/driver", ['driver_id' => $driver->id])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('deliveries', [
            'order_id' => $order->id,
            'driver_id' => $driver->id,
            'status' => Delivery::STATUS_ASSIGNED,
        ]);

        $this->actingAs($staff)
            ->put("/staff/orders/{$order->id}/status", ['status' => Order::STATUS_OUT_FOR_DELIVERY])
            ->assertSessionHasNoErrors();

        $this->assertSame(Delivery::STATUS_IN_TRANSIT, $order->delivery()->first()->status);
    }

    public function test_non_drivers_cannot_be_assigned(): void
    {
        $order = $this->order('delivery');
        $customer = User::factory()->customer()->create();

        $this->actingAs($this->staff())
            ->put("/staff/orders/{$order->id}/driver", ['driver_id' => $customer->id])
            ->assertSessionHasErrors('driver_id');
    }

    public function test_cancellation_only_from_pending_or_confirmed(): void
    {
        $pending = $this->order('pickup', Order::STATUS_PENDING);

        $this->actingAs($this->staff())
            ->put("/staff/orders/{$pending->id}/status", ['status' => Order::STATUS_CANCELLED])
            ->assertSessionHasNoErrors();

        $this->assertSame(Order::STATUS_CANCELLED, $pending->refresh()->status);

        $preparing = $this->order('pickup', Order::STATUS_PREPARING);

        $this->actingAs($this->staff())
            ->put("/staff/orders/{$preparing->id}/status", ['status' => Order::STATUS_CANCELLED])
            ->assertSessionHasErrors('status');

        $this->assertSame(Order::STATUS_PREPARING, $preparing->refresh()->status);
    }

    public function test_payment_cannot_be_recorded_before_pickup(): void
    {
        $order = $this->order('pickup', Order::STATUS_PENDING);

        $this->actingAs($this->staff())
            ->put("/staff/orders/{$order->id}/payment", ['method' => 'cash'])
            ->assertSessionHasErrors('payment');

        $this->assertSame(0, Payment::count());
    }

    public function test_payment_records_the_full_order_total(): void
    {
        $order = $this->order('pickup', Order::STATUS_PICKED_UP);
        $staff = $this->staff();

        $this->actingAs($staff)
            ->put("/staff/orders/{$order->id}/payment", ['method' => 'gcash'])
            ->assertSessionHasNoErrors();

        $payment = Payment::first();

        $this->assertSame('75.00', $payment->amount);
        $this->assertSame('gcash', $payment->method);
        $this->assertSame($staff->id, $payment->recorded_by);
        $this->assertSame('paid', $order->refresh()->payment_status);
    }

    public function test_delivery_payment_requires_delivered_status(): void
    {
        $order = $this->order('delivery', Order::STATUS_OUT_FOR_DELIVERY);

        $this->actingAs($this->staff())
            ->put("/staff/orders/{$order->id}/payment", ['method' => 'cash'])
            ->assertSessionHasErrors('payment');

        $order->update(['status' => Order::STATUS_DELIVERED]);

        $this->actingAs($this->staff())
            ->put("/staff/orders/{$order->id}/payment", ['method' => 'cash'])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Payment::count());
    }

    public function test_cannot_record_payment_twice(): void
    {
        $order = $this->order('pickup', Order::STATUS_PICKED_UP);
        $staff = $this->staff();

        $this->actingAs($staff)->put("/staff/orders/{$order->id}/payment", ['method' => 'cash']);
        $this->actingAs($staff)
            ->put("/staff/orders/{$order->id}/payment", ['method' => 'cash'])
            ->assertSessionHasErrors('payment');

        $this->assertSame(1, Payment::count());
    }

    public function test_ajax_status_change_returns_json(): void
    {
        $order = $this->order('pickup', Order::STATUS_PENDING);

        $this->actingAs($this->staff())
            ->putJson("/staff/orders/{$order->id}/status", ['status' => Order::STATUS_CONFIRMED])
            ->assertOk()
            ->assertJsonPath('message', 'Order updated to confirmed.');

        $this->assertSame(Order::STATUS_CONFIRMED, $order->refresh()->status);
    }

    public function test_ajax_invalid_status_returns_422_json(): void
    {
        $order = $this->order('pickup', Order::STATUS_PENDING);

        $this->actingAs($this->staff())
            ->putJson("/staff/orders/{$order->id}/status", ['status' => Order::STATUS_COMPLETED])
            ->assertStatus(422);
    }

    public function test_drivers_cannot_record_payments(): void
    {
        $order = $this->order('pickup', Order::STATUS_PICKED_UP);

        $this->actingAs($this->driver())
            ->put("/staff/orders/{$order->id}/payment", ['method' => 'cash'])
            ->assertForbidden();

        $this->assertSame(0, Payment::count());
    }

    // rule 14: containers move once, at completion
    public function test_completion_moves_container_counts(): void
    {
        $order = $this->order('pickup', Order::STATUS_PICKED_UP);

        $before = ContainerInventory::orderBy('id')->pluck('quantity', 'status')->all();

        $this->actingAs($this->staff())
            ->put("/staff/orders/{$order->id}/status", ['status' => Order::STATUS_COMPLETED])
            ->assertSessionHasNoErrors();

        $after = ContainerInventory::orderBy('id')->pluck('quantity', 'status')->all();

        // 3 gallons of refill: full -3, with_customer +3
        $this->assertSame($before['at_station_full'] - 3, $after['at_station_full']);
        $this->assertSame($before['with_customer'] + 3, $after['with_customer']);
    }

    public function test_completion_with_swap_returns_empty_containers(): void
    {
        $product = Product::create(['name' => 'R '.uniqid(), 'price' => 25, 'unit' => 'gallon', 'is_active' => true]);
        $order = Order::factory()->create([
            'order_type' => 'pickup',
            'status' => Order::STATUS_PICKED_UP,
            'container_swap' => true,
            'container_swap_qty' => 2,
        ]);
        $order->items()->create(['product_id' => $product->id, 'quantity' => 3, 'price' => 25, 'subtotal' => 75]);

        $before = ContainerInventory::orderBy('id')->pluck('quantity', 'status')->all();

        $this->actingAs($this->staff())
            ->put("/staff/orders/{$order->id}/status", ['status' => Order::STATUS_COMPLETED])
            ->assertSessionHasNoErrors();

        $after = ContainerInventory::orderBy('id')->pluck('quantity', 'status')->all();

        $this->assertSame($before['at_station_full'] - 3, $after['at_station_full']);
        $this->assertSame($before['with_customer'] + 3 - 2, $after['with_customer']);
        $this->assertSame($before['at_station_empty'] + 2, $after['at_station_empty']);
    }

    public function test_completion_writes_the_swap_transaction_only(): void
    {
        $product = Product::create(['name' => 'R '.uniqid(), 'price' => 25, 'unit' => 'gallon', 'is_active' => true]);
        $order = Order::factory()->create([
            'order_type' => 'pickup',
            'status' => Order::STATUS_PICKED_UP,
            'container_swap' => true,
            'container_swap_qty' => 2,
        ]);
        $order->items()->create(['product_id' => $product->id, 'quantity' => 3, 'price' => 25, 'subtotal' => 75]);

        $this->actingAs($this->staff())
            ->put("/staff/orders/{$order->id}/status", ['status' => Order::STATUS_COMPLETED]);

        // returns are recorded by the driver on a delivery run, never here
        $this->assertSame(1, ContainerTransaction::where('order_id', $order->id)->where('type', 'swap')->count());
        $this->assertSame(0, ContainerTransaction::where('order_id', $order->id)->where('type', 'return')->count());
    }

    public function test_cancelled_orders_never_move_containers(): void
    {
        $order = $this->order('pickup', Order::STATUS_PENDING);
        $before = ContainerInventory::orderBy('id')->pluck('quantity', 'status')->all();

        $this->actingAs($this->staff())
            ->put("/staff/orders/{$order->id}/status", ['status' => Order::STATUS_CANCELLED]);

        $after = ContainerInventory::orderBy('id')->pluck('quantity', 'status')->all();

        $this->assertSame($before, $after);
        $this->assertSame(0, ContainerTransaction::count());
    }

    public function test_new_container_purchase_draws_down_stock_and_logs_it(): void
    {
        $product = Product::create(['name' => 'NC '.uniqid(), 'price' => 250, 'unit' => 'piece', 'is_active' => true]);
        $order = Order::factory()->create(['order_type' => 'pickup', 'status' => Order::STATUS_PICKED_UP, 'total_amount' => 500]);
        $order->items()->create(['product_id' => $product->id, 'quantity' => 2, 'price' => 250, 'subtotal' => 500]);

        $before = InventoryItem::where('name', 'new_container')->value('quantity');

        $this->actingAs($this->staff())
            ->put("/staff/orders/{$order->id}/status", ['status' => Order::STATUS_COMPLETED]);

        $this->assertSame($before - 2, InventoryItem::where('name', 'new_container')->value('quantity'));
        $this->assertSame(1, InventoryLog::where('item_name', 'new_container')->count());
        $this->assertSame(1, ContainerTransaction::where('order_id', $order->id)->where('type', 'purchase')->count());
    }

    public function test_dispenser_rental_moves_available_to_rented(): void
    {
        $product = Product::create(['name' => 'D '.uniqid(), 'price' => 150, 'unit' => 'month', 'is_active' => true]);
        $order = Order::factory()->create(['order_type' => 'pickup', 'status' => Order::STATUS_PICKED_UP, 'total_amount' => 150]);
        $order->items()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 150, 'subtotal' => 150]);

        $availableBefore = InventoryItem::where('name', 'dispenser_available')->value('quantity');
        $rentedBefore = InventoryItem::where('name', 'dispenser_rented')->value('quantity');

        $this->actingAs($this->staff())
            ->put("/staff/orders/{$order->id}/status", ['status' => Order::STATUS_COMPLETED]);

        $this->assertSame($availableBefore - 1, InventoryItem::where('name', 'dispenser_available')->value('quantity'));
        $this->assertSame($rentedBefore + 1, InventoryItem::where('name', 'dispenser_rented')->value('quantity'));
    }

    public function test_staff_can_view_inventory_and_containers(): void
    {
        $staff = $this->staff();

        $this->actingAs($staff)->get('/staff/inventory')->assertOk()->assertSee('new container');
        $this->actingAs($staff)->get('/staff/containers')->assertOk()->assertSee('at station full');
    }
}
