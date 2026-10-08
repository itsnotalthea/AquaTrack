<?php

namespace Tests\Feature;

use App\Models\ContainerInventory;
use App\Models\Delivery;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_is_resolved_from_email_domain(): void
    {
        $this->assertSame(User::ROLE_ADMIN, User::resolveRole('tom@admin.com'));
        $this->assertSame(User::ROLE_STAFF, User::resolveRole('althea@staff.com'));
        $this->assertSame(User::ROLE_DRIVER, User::resolveRole('walter@delivery.com'));
        $this->assertSame(User::ROLE_CUSTOMER, User::resolveRole('juan@gmail.com'));
    }

    public function test_role_resolution_is_case_insensitive(): void
    {
        $this->assertSame(User::ROLE_ADMIN, User::resolveRole('Tom@Admin.COM'));
    }

    public function test_delivery_order_follows_defined_flow(): void
    {
        $order = $this->order('delivery', Order::STATUS_PENDING);

        $this->assertTrue($order->canTransitionTo(Order::STATUS_CONFIRMED));
        $order->status = Order::STATUS_CONFIRMED;

        $this->assertTrue($order->canTransitionTo(Order::STATUS_PREPARING));
        $order->status = Order::STATUS_PREPARING;

        $this->assertTrue($order->canTransitionTo(Order::STATUS_OUT_FOR_DELIVERY));
        $this->assertFalse($order->canTransitionTo(Order::STATUS_READY_FOR_PICKUP));
        $order->status = Order::STATUS_OUT_FOR_DELIVERY;

        $this->assertTrue($order->canTransitionTo(Order::STATUS_DELIVERED));
        $order->status = Order::STATUS_DELIVERED;

        $this->assertTrue($order->canTransitionTo(Order::STATUS_COMPLETED));
        $order->status = Order::STATUS_COMPLETED;

        $this->assertSame([], $order->allowedTransitions());
    }

    public function test_pickup_order_follows_defined_flow(): void
    {
        $order = $this->order('pickup', Order::STATUS_PREPARING);

        $this->assertTrue($order->canTransitionTo(Order::STATUS_READY_FOR_PICKUP));
        $this->assertFalse($order->canTransitionTo(Order::STATUS_OUT_FOR_DELIVERY));

        $order->status = Order::STATUS_READY_FOR_PICKUP;

        $this->assertTrue($order->canTransitionTo(Order::STATUS_PICKED_UP));
    }

    public function test_cancellation_is_only_allowed_when_pending_or_confirmed(): void
    {
        $this->assertTrue($this->order('delivery', Order::STATUS_PENDING)->canBeCancelled());
        $this->assertTrue($this->order('delivery', Order::STATUS_CONFIRMED)->canBeCancelled());

        $this->assertFalse($this->order('delivery', Order::STATUS_PREPARING)->canBeCancelled());
        $this->assertFalse($this->order('delivery', Order::STATUS_DELIVERED)->canBeCancelled());
        $this->assertFalse($this->order('delivery', Order::STATUS_COMPLETED)->canBeCancelled());
        $this->assertFalse($this->order('delivery', Order::STATUS_CANCELLED)->canBeCancelled());
    }

    public function test_delivery_status_progresses_forward_only(): void
    {
        $delivery = new Delivery(['status' => Delivery::STATUS_ASSIGNED]);

        $this->assertTrue($delivery->canTransitionTo(Delivery::STATUS_IN_TRANSIT));
        $this->assertFalse($delivery->canTransitionTo(Delivery::STATUS_DELIVERED));

        $delivery->status = Delivery::STATUS_DELIVERED;

        $this->assertTrue($delivery->canTransitionTo(Delivery::STATUS_RETURNED_TO_STATION));
        $this->assertFalse($delivery->canTransitionTo(Delivery::STATUS_ASSIGNED));

        $delivery->status = Delivery::STATUS_RETURNED_TO_STATION;

        $this->assertSame([], $delivery->allowedTransitions());
    }

    public function test_container_fill_ratio_uses_station_counts(): void
    {
        $inventory = new ContainerInventory;
        $inventory->setAttribute(ContainerInventory::AT_STATION_FULL, 40);
        $inventory->setAttribute(ContainerInventory::AT_STATION_EMPTY, 60);

        $this->assertEqualsWithDelta(0.4, $inventory->fill_ratio, 0.0001);
    }

    public function test_container_fill_ratio_is_zero_with_no_containers_at_station(): void
    {
        $inventory = new ContainerInventory;
        $inventory->setAttribute(ContainerInventory::AT_STATION_FULL, 0);
        $inventory->setAttribute(ContainerInventory::AT_STATION_EMPTY, 0);

        $this->assertSame(0.0, $inventory->fill_ratio);
    }

    public function test_inventory_item_reports_low_stock_at_or_below_threshold(): void
    {
        $item = new InventoryItem(['quantity' => 5, 'threshold' => 5]);

        $this->assertTrue($item->isLowStock());

        $item->quantity = 6;

        $this->assertFalse($item->isLowStock());
    }

    public function test_delivery_fee_setting_round_trips(): void
    {
        Setting::put(Setting::DELIVERY_FEE, 30);

        $this->assertSame(30.0, Setting::deliveryFee());

        Setting::put(Setting::DELIVERY_FEE, 45);

        $this->assertSame(45.0, Setting::deliveryFee());
    }

    public function test_seeded_users_are_hashed_and_internal_roles_match_domain(): void
    {
        $admin = User::factory()->create([
            'email' => 'test@admin.com',
            'role' => User::ROLE_ADMIN,
            'first_name' => 'Tom',
            'last_name' => 'Reyes',
        ]);

        $this->assertNotSame('password', $admin->password);
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isStaff());
        $this->assertSame('Tom Reyes', $admin->name);
    }

    public function test_full_address_joins_only_present_parts(): void
    {
        $user = User::factory()->create([
            'house_no' => '12',
            'street' => 'Katipunan Ave',
            'subdivision' => null,
            'city' => 'Marikina City',
        ]);

        $this->assertSame('12, Katipunan Ave, Marikina City', $user->full_address);
    }

    private function order(string $type, string $status): Order
    {
        return new Order(['order_type' => $type, 'status' => $status]);
    }
}
