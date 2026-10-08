<?php

namespace App\Services;

use App\Models\ContainerInventory;
use App\Models\ContainerTransaction;
use App\Models\InventoryItem;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * Applies the side effects that happen when an order reaches completed
 * container counts, stock levels, container transactions, and audit logs.
 */
class OrderFulfillment
{
    public function handle(Order $order): void
    {
        $order->loadMissing('items.product');

        DB::transaction(function () use ($order) {
            $this->moveContainers($order);
            $this->moveStock($order);
            $this->recordContainerTransactions($order);
        });
    }

    // at_station_full -Q, with_customer +Q; returned empties land at the station
    private function moveContainers(Order $order): void
    {
        $item = $order->items->first();

        if (! $item || $item->product->unit !== Product::UNIT_GALLON) {
            return;
        }

        $quantity = $item->quantity;

        // the driver reports what actually came back, fall back to the ordered swap
        $returned = $order->delivery?->returned_containers ?? $order->container_swap_qty ?? 0;
        $returned = (int) $returned;

        $this->shift(ContainerInventory::AT_STATION_FULL, -$quantity);
        $this->shift(ContainerInventory::WITH_CUSTOMER, $quantity);

        if ($returned > 0) {
            $this->shift(ContainerInventory::WITH_CUSTOMER, -$returned);
            $this->shift(ContainerInventory::AT_STATION_EMPTY, $returned);
        }
    }

    // new containers and dispensers draw down their stock lines
    private function moveStock(Order $order): void
    {
        $item = $order->items->first();

        if (! $item) {
            return;
        }

        $quantity = $item->quantity;

        match ($item->product->unit) {
            Product::UNIT_PIECE => $this->adjustItem('new_container', -$quantity, $order, 'new container sold'),
            Product::UNIT_MONTH => $this->adjustItem('dispenser_available', -$quantity, $order, 'dispenser rented'),
            default => null,
        };

        if ($item->product->unit === Product::UNIT_MONTH) {
            $this->adjustItem('dispenser_rented', $quantity, $order, 'dispenser rental started');
        }
    }

    // swaps and purchases are recorded here, returns come from the driver's own form
    private function recordContainerTransactions(Order $order): void
    {
        $item = $order->items->first();

        if (! $item) {
            return;
        }

        $swap = (int) ($order->container_swap_qty ?? 0);

        if ($swap > 0) {
            ContainerTransaction::create([
                'customer_id' => $order->customer_id,
                'order_id' => $order->id,
                'type' => ContainerTransaction::TYPE_SWAP,
                'quantity' => $swap,
            ]);
        }

        if ($item->product->unit === Product::UNIT_PIECE) {
            ContainerTransaction::create([
                'customer_id' => $order->customer_id,
                'order_id' => $order->id,
                'type' => ContainerTransaction::TYPE_PURCHASE,
                'quantity' => $item->quantity,
            ]);
        }
    }

    private function shift(string $status, int $delta): void
    {
        ContainerInventory::where('status', $status)->firstOrFail()->increment('quantity', $delta);
    }

    private function adjustItem(string $name, int $delta, Order $order, string $reason): void
    {
        $item = InventoryItem::where('name', $name)->first();

        if (! $item) {
            return;
        }

        $item->increment('quantity', $delta);

        InventoryLog::create([
            'item_name' => $name,
            'change' => $delta,
            'reason' => $reason,
            'reference_id' => $order->id,
        ]);
    }
}
