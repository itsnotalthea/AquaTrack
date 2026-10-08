<?php

namespace Database\Seeders;

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
            ],
            [
                'name' => 'New Container',
                'description' => 'New reusable 5-gallon container, tracked in container inventory.',
                'price' => 250.00,
                'unit' => Product::UNIT_PIECE,
            ],
            [
                'name' => 'Dispenser Rental',
                'description' => 'Table-top dispenser rental per month.',
                'price' => 150.00,
                'unit' => Product::UNIT_MONTH,
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(['name' => $product['name']], $product + ['is_active' => true]);
        }
    }
}
