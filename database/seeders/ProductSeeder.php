<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name' => '5-Gallon Refill',
                'description' => 'Refill of a reusable 5-gallon container.',
                'price' => 25.00,
                'unit' => Product::UNIT_GALLON,
                'fulfillment_type' => Product::FULFILLMENT_REFILL,
                'inventory_item_id' => null,
            ],
            [
                'name' => 'New Container',
                'description' => 'New reusable 5-gallon container, tracked in container inventory.',
                'price' => 250.00,
                'unit' => Product::UNIT_PIECE,
                'fulfillment_type' => Product::FULFILLMENT_NEW_CONTAINER,
                'inventory_item_id' => InventoryItem::where('name', 'new_container')->value('id'),
            ],
            [
                'name' => 'Dispenser Rental',
                'description' => 'Table-top dispenser rental per month.',
                'price' => 150.00,
                'unit' => Product::UNIT_MONTH,
                'fulfillment_type' => Product::FULFILLMENT_DISPENSER_RENTAL,
                'inventory_item_id' => InventoryItem::where('name', 'dispenser_available')->value('id'),
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(['name' => $product['name']], $product + ['is_active' => true]);
        }
    }
}
