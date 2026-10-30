<?php

namespace App\Services;

use App\Models\ContainerInventory;
use App\Models\InventoryItem;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * Every inventory movement goes through this service so quantities can never
 * silently drop below zero and every change gets an audit row.
 */
class StockService
{
    public function containerQuantity(string $status): int
    {
        return (int) ContainerInventory::where('status', $status)->value('quantity');
    }

    public function containerTracked(string $status): bool
    {
        return ContainerInventory::where('status', $status)->exists();
    }

    public function inventoryQuantity(string $name): int
    {
        return (int) InventoryItem::where('name', $name)->value('quantity');
    }

    public function inventoryTracked(string $name): bool
    {
        return $name !== '' && InventoryItem::where('name', $name)->exists();
    }

    /**
     * How many units of a product can currently be promised to a customer.
     * Refills brought in the customer's own container have no container cap.
     * Untracked reference rows mean inventory has not been set up yet, so no
     * artificial cap is applied (keeps fresh installs and tests usable).
     */
    public function availableFor(Product $product, ?string $containerMode = null): int
    {
        return match ($product->resolvedFulfillmentType()) {
            Product::FULFILLMENT_REFILL => $containerMode === Product::CONTAINER_BRING_YOUR_OWN
                ? PHP_INT_MAX
                : ($this->containerTracked(ContainerInventory::AT_STATION_FULL)
                    ? $this->containerQuantity(ContainerInventory::AT_STATION_FULL)
                    : PHP_INT_MAX),
            Product::FULFILLMENT_NEW_CONTAINER => $this->availableNewContainers(),
            Product::FULFILLMENT_DISPENSER_RENTAL => $this->inventoryTracked('dispenser_available')
                ? $this->inventoryQuantity('dispenser_available')
                : PHP_INT_MAX,
            default => $product->inventory_item_id && $this->inventoryTracked($product->inventoryItem?->name ?? '')
                ? $this->inventoryQuantity($product->inventoryItem->name)
                : PHP_INT_MAX,
        };
    }

    private function availableNewContainers(): int
    {
        $limits = [];

        if ($this->containerTracked(ContainerInventory::AT_STATION_FULL)) {
            $limits[] = $this->containerQuantity(ContainerInventory::AT_STATION_FULL);
        }

        if ($this->inventoryTracked('new_container')) {
            $limits[] = $this->inventoryQuantity('new_container');
        }

        return $limits === [] ? PHP_INT_MAX : min($limits);
    }

    /**
     * @param  array<int, array{product: Product, quantity: int, container_mode?: ?string}>  $lines
     *
     * @throws InsufficientStockException
     */
    public function assertAvailable(array $lines): void
    {
        foreach ($lines as $line) {
            $product = $line['product'];
            $quantity = (int) $line['quantity'];
            $available = $this->availableFor($product, $line['container_mode'] ?? null);

            if ($quantity > $available) {
                throw new InsufficientStockException($product->name, $quantity, $available);
            }
        }
    }

    public function shiftContainer(string $status, int $delta, ?Order $order = null, string $reason = 'container adjustment'): void
    {
        if ($delta === 0) {
            return;
        }

        $row = ContainerInventory::where('status', $status)->lockForUpdate()->first();

        if (! $row) {
            return;
        }

        $before = (int) $row->quantity;
        $after = $before + $delta;

        if ($after < 0) {
            throw new InsufficientStockException(
                str_replace('_', ' ', $status),
                abs($delta),
                $before
            );
        }

        $row->update(['quantity' => $after]);

        InventoryLog::create([
            'item_name' => $status,
            'change' => $delta,
            'reason' => $reason,
            'reference_id' => $order?->id,
            'reference_type' => $order ? Order::class : null,
            'user_id' => auth()->id(),
            'from_status' => $delta < 0 ? $status : null,
            'to_status' => $delta > 0 ? $status : null,
            'quantity_before' => $before,
            'quantity_after' => $after,
        ]);
    }

    public function moveContainer(
        string $from,
        string $to,
        int $quantity,
        ?Order $order = null,
        string $reason = 'container movement'
    ): void {
        if ($quantity <= 0) {
            return;
        }

        $this->shiftContainer($from, -$quantity, $order, $reason);
        $this->shiftContainer($to, $quantity, $order, $reason);
    }

    public function adjustInventoryItem(
        string $name,
        int $delta,
        ?Order $order = null,
        string $reason = 'inventory adjustment',
        ?string $referenceType = null
    ): void {
        if ($delta === 0) {
            return;
        }

        $item = InventoryItem::where('name', $name)->lockForUpdate()->first();

        if (! $item) {
            return;
        }

        $before = (int) $item->quantity;
        $after = $before + $delta;

        if ($after < 0) {
            throw new InsufficientStockException(
                str_replace('_', ' ', $name),
                abs($delta),
                $before
            );
        }

        $item->update(['quantity' => $after]);

        InventoryLog::create([
            'item_name' => $name,
            'change' => $delta,
            'reason' => $reason,
            'reference_id' => $order?->id,
            'reference_type' => $referenceType ?? ($order ? Order::class : null),
            'user_id' => auth()->id(),
            'quantity_before' => $before,
            'quantity_after' => $after,
        ]);
    }

    public function moveInventoryItem(
        string $from,
        string $to,
        int $quantity,
        ?Order $order = null,
        string $reason = 'inventory movement'
    ): void {
        if ($quantity <= 0) {
            return;
        }

        $this->adjustInventoryItem($from, -$quantity, $order, $reason);
        $this->adjustInventoryItem($to, $quantity, $order, $reason);
    }
}
