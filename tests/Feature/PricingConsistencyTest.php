<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Setting;
use Database\Seeders\ProductSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The order form is the price source of truth. These keep the marketing copy
 * on /services from drifting away from it again.
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

    public function test_service_cards_match_the_product_prices(): void
    {
        $refill = Product::where('unit', Product::UNIT_GALLON)->first();
        $container = Product::where('unit', Product::UNIT_PIECE)->first();
        $dispenser = Product::where('unit', Product::UNIT_MONTH)->first();

        $html = $this->get('/services')->assertOk()->getContent();

        $this->assertStringContainsString('₱'.number_format((float) $refill->price, 0).' per gallon', $html);
        $this->assertStringContainsString('₱'.number_format((float) $container->price, 0).' each', $html);
        $this->assertStringContainsString('₱'.number_format((float) $dispenser->price, 0).' per month', $html);
    }

    public function test_service_cards_match_the_delivery_fee_setting(): void
    {
        $html = $this->get('/services')->assertOk()->getContent();

        $this->assertStringContainsString(
            '₱'.number_format(Setting::deliveryFee(), 0).' delivery fee per order',
            $html
        );
    }

    public function test_order_form_options_match_the_catalog(): void
    {
        $html = $this->get('/services')->assertOk()->getContent();

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
