<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_admin_can_list_products(): void
    {
        $product = Product::create([
            'name' => 'Refill',
            'price' => 25,
            'unit' => Product::UNIT_GALLON,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin())
            ->get('/admin/products')
            ->assertOk()
            ->assertSee('Refill');
    }

    public function test_admin_can_create_a_product(): void
    {
        $this->actingAs($this->admin())->post('/admin/products', [
            'name' => 'Bigger Container',
            'description' => 'Larger capacity',
            'price' => 400,
            'unit' => 'piece',
            'is_active' => true,
        ])->assertRedirect('/admin/products');

        $this->assertDatabaseHas('products', ['name' => 'Bigger Container']);
    }

    public function test_price_and_unit_are_validated(): void
    {
        $this->actingAs($this->admin())->post('/admin/products', [
            'name' => 'Bad',
            'price' => -5,
            'unit' => 'banana',
        ])->assertSessionHasErrors(['price', 'unit']);
    }

    public function test_admin_can_deactivate_a_product_used_in_past_orders(): void
    {
        $product = Product::create([
            'name' => 'Retired Refill',
            'price' => 25,
            'unit' => Product::UNIT_GALLON,
            'is_active' => true,
        ]);

        $order = Order::factory()->create();
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 25,
            'subtotal' => 50,
        ]);

        $this->actingAs($this->admin())->put("/admin/products/{$product->id}", [
            'name' => $product->name,
            'price' => 25,
            'unit' => 'gallon',
            'is_active' => false,
        ])->assertRedirect('/admin/products');

        $this->assertFalse($product->refresh()->is_active);
    }

    public function test_product_used_in_past_orders_cannot_be_deleted(): void
    {
        $product = Product::create([
            'name' => 'Locked Product',
            'price' => 25,
            'unit' => Product::UNIT_GALLON,
            'is_active' => true,
        ]);

        $order = Order::factory()->create();
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 25,
            'subtotal' => 25,
        ]);

        $this->actingAs($this->admin())
            ->delete("/admin/products/{$product->id}")
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_unused_product_can_be_deleted(): void
    {
        $product = Product::create([
            'name' => 'Unused',
            'price' => 10,
            'unit' => Product::UNIT_PIECE,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin())
            ->delete("/admin/products/{$product->id}")
            ->assertRedirect('/admin/products');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
