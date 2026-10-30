<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\ProductSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The customer order screen is the live price source; Services remains static
 * so marketing pages do not require a database connection.
 */
class PricingConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ProductSeeder::class);
        $this->seed(SettingSeeder::class);
    }

    public function test_services_page_describes_offerings_without_live_catalog_data(): void
    {
        $html = $this->get('/services')->assertOk()->getContent();

        $this->assertStringContainsString('Water refills', $html);
        $this->assertStringContainsString('New containers', $html);
        $this->assertStringContainsString('Dispenser rentals', $html);
        $this->assertStringContainsString('Current prices and availability are shown when you place an order.', $html);

        foreach (Product::all() as $product) {
            $this->assertStringNotContainsString($product->name, $html);
        }
    }

    public function test_services_page_defers_delivery_fee_to_checkout(): void
    {
        $html = $this->get('/services')->assertOk()->getContent();

        $this->assertStringContainsString('Any delivery fee is shown before you submit your order.', $html);
        $this->assertStringNotContainsString('₱'.number_format(Setting::deliveryFee(), 2).' per order', $html);
    }

    public function test_order_form_options_match_the_catalog(): void
    {
        $customer = User::factory()->customer()->create();
        $html = $this->actingAs($customer)->get('/customer/orders/create')->assertOk()->getContent();

        foreach (Product::orderBy('name')->get() as $product) {
            $this->assertStringContainsString(
                '₱'.number_format((float) $product->price, 0),
                $html,
                $product->name.' price is not shown on the form'
            );
        }
    }

    public function test_the_documented_prices_are_the_spec_ones(): void
    {
        // instructions.md: refill 25, new container 250, dispenser 150, delivery fee 30
        $this->assertSame(25.0, (float) Product::where('unit', Product::UNIT_GALLON)->value('price'));
        $this->assertSame(250.0, (float) Product::where('unit', Product::UNIT_PIECE)->value('price'));
        $this->assertSame(150.0, (float) Product::where('unit', Product::UNIT_MONTH)->value('price'));
        $this->assertSame(30.0, Setting::deliveryFee());
    }
}
