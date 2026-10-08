<?php

namespace Tests\Feature\Admin;

use App\Models\InventoryItem;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInventoryAndSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_admin_can_view_the_thresholds_editor(): void
    {
        InventoryItem::create(['name' => 'new_container', 'quantity' => 30, 'threshold' => 10]);

        $this->actingAs($this->admin())
            ->get('/admin/inventory')
            ->assertOk()
            ->assertSee('new container');
    }

    public function test_admin_can_update_a_threshold(): void
    {
        $item = InventoryItem::create(['name' => 'dispenser_available', 'quantity' => 12, 'threshold' => 5]);

        $this->actingAs($this->admin())
            ->put('/admin/inventory', ['thresholds' => [$item->id => 20]])
            ->assertSessionHasNoErrors();

        $this->assertSame(20, $item->refresh()->threshold);
    }

    public function test_inventory_update_does_not_change_quantity(): void
    {
        $item = InventoryItem::create(['name' => 'new_container', 'quantity' => 30, 'threshold' => 10]);

        $this->actingAs($this->admin())
            ->put('/admin/inventory', ['thresholds' => [$item->id => 25]]);

        $this->assertSame(30, $item->refresh()->quantity);
    }

    public function test_thresholds_reject_negative_values(): void
    {
        $item = InventoryItem::create(['name' => 'new_container', 'quantity' => 30, 'threshold' => 10]);

        $this->actingAs($this->admin())
            ->put('/admin/inventory', ['thresholds' => [$item->id => -1]])
            ->assertSessionHasErrors('thresholds.'.$item->id);

        $this->assertSame(10, $item->refresh()->threshold);
    }

    public function test_inventory_page_flags_low_stock_items(): void
    {
        InventoryItem::create(['name' => 'dispenser_maintenance', 'quantity' => 2, 'threshold' => 3]);
        InventoryItem::create(['name' => 'new_container', 'quantity' => 30, 'threshold' => 10]);

        $this->actingAs($this->admin())
            ->get('/admin/inventory')
            ->assertOk()
            ->assertSee('Low stock');
    }

    public function test_admin_can_view_and_update_the_delivery_fee(): void
    {
        Setting::put(Setting::DELIVERY_FEE, 30);

        $this->actingAs($this->admin())
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee('30.00');

        $this->actingAs($this->admin())
            ->put('/admin/settings', ['delivery_fee' => 45])
            ->assertSessionHasNoErrors();

        $this->assertSame(45.0, Setting::deliveryFee());
    }

    public function test_delivery_fee_rejects_negative_values(): void
    {
        $this->actingAs($this->admin())
            ->put('/admin/settings', ['delivery_fee' => -10])
            ->assertSessionHasErrors('delivery_fee');
    }

    public function test_staff_cannot_reach_admin_inventory_or_settings(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get('/admin/inventory')->assertForbidden();
        $this->actingAs($staff)->get('/admin/settings')->assertForbidden();
        $this->actingAs($staff)->put('/admin/settings', ['delivery_fee' => 1])->assertForbidden();
    }
}
