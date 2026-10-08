<?php

namespace Database\Seeders;

use App\Models\ContainerInventory;
use App\Models\InventoryItem;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $containerCounts = [
            ContainerInventory::AT_STATION_FULL => 40,
            ContainerInventory::AT_STATION_EMPTY => 25,
            ContainerInventory::WITH_CUSTOMER => 18,
            ContainerInventory::OUT_FOR_DELIVERY => 7,
            ContainerInventory::LOST_DAMAGED => 3,
        ];

        foreach ($containerCounts as $status => $quantity) {
            ContainerInventory::updateOrCreate(['status' => $status], ['quantity' => $quantity]);
        }

        $items = [
            ['name' => 'new_container', 'quantity' => 30, 'threshold' => 10],
            ['name' => 'dispenser_available', 'quantity' => 12, 'threshold' => 5],
            ['name' => 'dispenser_rented', 'quantity' => 8, 'threshold' => 0],
            ['name' => 'dispenser_maintenance', 'quantity' => 2, 'threshold' => 3],
            ['name' => 'dispenser_lost', 'quantity' => 1, 'threshold' => 0],
        ];

        foreach ($items as $item) {
            InventoryItem::updateOrCreate(['name' => $item['name']], $item);
        }
    }
}
