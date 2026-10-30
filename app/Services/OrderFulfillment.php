<?php

namespace App\Services;

use App\Models\ContainerInventory;
use App\Models\ContainerTransaction;
use App\Models\Delivery;
use App\Models\DispenserRental;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

/**
 * Applies the physical side effects of an order: containers leaving the
 * station, stock being consumed, dispensers being rented, and the return leg.
 *
 * Movements happen at dispatch when possible and at completion for pickup
 * orders (or legacy flows that skip dispatch). The stock_dispatched_at marker
 * makes completion idempotent and prevents double-counting.
 */
class OrderFulfillment
{
    public function __construct(private StockService $stock) {}

    public function handle(Order $order): void
    {
        $order->loadMissing('items.product', 'delivery');

        DB::transaction(function () use ($order) {
            $returnedApplied = false;

            foreach ($order->items as $item) {
                $returned = 0;

                if (! $returnedApplied && $item->isExchangeRefill()) {
                    $returned = $this->returnedContainers($order);
                    $returnedApplied = true;
                }

                $this->completeItem($order, $item, $returned);
            }
        });
    }

    public function dispatch(Order $order): void
    {
        $order->loadMissing('items.product');

        DB::transaction(function () use ($order) {
            if ($order->stock_dispatched_at) {
                return;
            }

            foreach ($order->items as $item) {
                $this->dispatchItem($order, $item);
            }

            $order->update(['stock_dispatched_at' => now()]);
        });
    }

    /**
     * A failed delivery puts untouched stock back where it came from so the
     * order can be retried or cancelled without leaking inventory.
     */
    public function fail(Order $order, string $reason, ?Delivery $delivery = null): void
    {
        $order->loadMissing('items.product');

        DB::transaction(function () use ($order, $reason, $delivery) {
            if ($order->stock_dispatched_at) {
                foreach ($order->items as $item) {
                    $this->reverseDispatchItem($order, $item);
                }
            }

            $delivery?->update([
                'status' => Delivery::STATUS_FAILED,
                'failed_at' => now(),
                'failure_reason' => $reason,
            ]);

            $order->update([
                'status' => Order::STATUS_DELIVERY_FAILED,
                'failed_at' => now(),
                'failure_reason' => $reason,
                'stock_dispatched_at' => null,
            ]);

            OrderStatusLog::create([
                'order_id' => $order->id,
                'from_status' => Order::STATUS_OUT_FOR_DELIVERY,
                'to_status' => Order::STATUS_DELIVERY_FAILED,
                'user_id' => auth()->id(),
                'notes' => $reason,
            ]);
        });
    }

    /**
     * Partial or full dispenser return. Good units go back to available,
     * damaged units go to maintenance, lost units are written off.
     */
    public function recordDispenserReturn(
        DispenserRental $rental,
        int $quantity,
        string $condition = DispenserRental::CONDITION_GOOD,
        ?string $notes = null
    ): void {
        if ($quantity < 1 || $quantity > $rental->remaining) {
            throw new \InvalidArgumentException('The return quantity is outside the rented amount.');
        }

        DB::transaction(function () use ($rental, $quantity, $condition, $notes) {
            $destination = match ($condition) {
                DispenserRental::CONDITION_DAMAGED => 'dispenser_maintenance',
                DispenserRental::CONDITION_LOST => 'dispenser_lost',
                default => 'dispenser_available',
            };

            $order = $rental->order;
            $this->stock->adjustInventoryItem('dispenser_rented', -$quantity, $order, 'dispenser returned');
            $this->adjustIfTracked($destination, $quantity, $order, 'dispenser return: '.$condition);

            $returned = $rental->returned_quantity + $quantity;
            $finished = $returned >= $rental->quantity;

            $rental->update([
                'returned_quantity' => $returned,
                'returned_at' => $finished ? now() : null,
                'status' => match (true) {
                    $finished && $condition === DispenserRental::CONDITION_LOST => DispenserRental::STATUS_LOST,
                    $finished && $condition === DispenserRental::CONDITION_DAMAGED => DispenserRental::STATUS_DAMAGED,
                    $finished => DispenserRental::STATUS_RETURNED,
                    default => $rental->status,
                },
                'condition' => $condition,
                'notes' => $notes ?? $rental->notes,
                'handled_by' => auth()->id(),
            ]);
        });
    }

    private function dispatchItem(Order $order, $item): void
    {
        $quantity = (int) $item->quantity;

        match ($item->resolvedFulfillmentType()) {
            Product::FULFILLMENT_REFILL => $item->isExchangeRefill()
                ? $this->stock->moveContainer(
                    ContainerInventory::AT_STATION_FULL,
                    ContainerInventory::OUT_FOR_DELIVERY,
                    $quantity,
                    $order,
                    'refill loaded for delivery'
                )
                : null,
            Product::FULFILLMENT_NEW_CONTAINER => $this->dispatchNewContainer($order, $item),
            Product::FULFILLMENT_DISPENSER_RENTAL => $this->stock->moveInventoryItem(
                'dispenser_available',
                'dispenser_out_for_delivery',
                $quantity,
                $order,
                'dispenser loaded for delivery'
            ),
            default => null,
        };
    }

    private function completeItem(Order $order, $item, int $returned): void
    {
        $quantity = (int) $item->quantity;

        match ($item->resolvedFulfillmentType()) {
            Product::FULFILLMENT_REFILL => $item->isExchangeRefill()
                ? $this->completeExchangeRefill($order, $quantity, $returned)
                : null,
            Product::FULFILLMENT_NEW_CONTAINER => $this->completeNewContainer($order, $item),
            Product::FULFILLMENT_DISPENSER_RENTAL => $this->completeDispenser($order, $item),
            default => null,
        };
    }

    private function completeExchangeRefill(Order $order, int $quantity, int $returned): void
    {
        $from = $order->stock_dispatched_at
            ? ContainerInventory::OUT_FOR_DELIVERY
            : ContainerInventory::AT_STATION_FULL;

        $this->stock->moveContainer(
            $from,
            ContainerInventory::WITH_CUSTOMER,
            $quantity,
            $order,
            'refill handed to customer'
        );

        if ($returned > 0) {
            $this->stock->moveContainer(
                ContainerInventory::WITH_CUSTOMER,
                ContainerInventory::AT_STATION_EMPTY,
                $returned,
                $order,
                'empty containers returned'
            );

            ContainerTransaction::create([
                'customer_id' => $order->customer_id,
                'order_id' => $order->id,
                'type' => ContainerTransaction::TYPE_SWAP,
                'quantity' => $returned,
                'recorded_by' => auth()->id(),
            ]);
        }
    }

    private function dispatchNewContainer(Order $order, $item): void
    {
        $this->stock->moveContainer(
            ContainerInventory::AT_STATION_FULL,
            ContainerInventory::OUT_FOR_DELIVERY,
            (int) $item->quantity,
            $order,
            'new container loaded for delivery'
        );
    }

    private function completeNewContainer(Order $order, $item): void
    {
        $quantity = (int) $item->quantity;
        $from = $order->stock_dispatched_at
            ? ContainerInventory::OUT_FOR_DELIVERY
            : ContainerInventory::AT_STATION_FULL;

        $this->stock->moveContainer(
            $from,
            ContainerInventory::WITH_CUSTOMER,
            $quantity,
            $order,
            'new container sold'
        );

        $this->stock->adjustInventoryItem('new_container', -$quantity, $order, 'new container sold');

        ContainerTransaction::create([
            'customer_id' => $order->customer_id,
            'order_id' => $order->id,
            'type' => ContainerTransaction::TYPE_PURCHASE,
            'quantity' => $quantity,
            'recorded_by' => auth()->id(),
        ]);
    }

    private function completeDispenser(Order $order, $item): void
    {
        $quantity = (int) $item->quantity;

        if ($order->stock_dispatched_at) {
            $this->stock->moveInventoryItem(
                'dispenser_out_for_delivery',
                'dispenser_rented',
                $quantity,
                $order,
                'dispenser rental started'
            );
        } else {
            $this->stock->moveInventoryItem(
                'dispenser_available',
                'dispenser_rented',
                $quantity,
                $order,
                'dispenser rental started'
            );
        }

        $days = max(1, (int) Setting::get(Setting::DISPENSER_RENTAL_DAYS, 30));

        DispenserRental::firstOrCreate(
            ['order_item_id' => $item->id],
            [
                'order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'product_id' => $item->product_id,
                'quantity' => $quantity,
                'returned_quantity' => 0,
                'started_at' => now(),
                'due_at' => now()->addDays($days),
                'status' => DispenserRental::STATUS_ACTIVE,
                'handled_by' => auth()->id(),
            ]
        );
    }

    private function reverseDispatchItem(Order $order, $item): void
    {
        $quantity = (int) $item->quantity;

        match ($item->resolvedFulfillmentType()) {
            Product::FULFILLMENT_REFILL => $item->isExchangeRefill()
                ? $this->stock->moveContainer(
                    ContainerInventory::OUT_FOR_DELIVERY,
                    ContainerInventory::AT_STATION_FULL,
                    $quantity,
                    $order,
                    'failed delivery: refill returned to station'
                )
                : null,
            Product::FULFILLMENT_NEW_CONTAINER => $this->stock->moveContainer(
                ContainerInventory::OUT_FOR_DELIVERY,
                ContainerInventory::AT_STATION_FULL,
                $quantity,
                $order,
                'failed delivery: new container returned to station'
            ),
            Product::FULFILLMENT_DISPENSER_RENTAL => $this->stock->moveInventoryItem(
                'dispenser_out_for_delivery',
                'dispenser_available',
                $quantity,
                $order,
                'failed delivery: dispenser returned to station'
            ),
            default => null,
        };
    }

    private function returnedContainers(Order $order): int
    {
        if ($order->isDelivery()) {
            return (int) ($order->delivery?->returned_containers ?? 0);
        }

        if ((int) $order->returned_containers > 0) {
            return (int) $order->returned_containers;
        }

        return (int) ($order->container_swap_qty ?? 0);
    }

    private function adjustIfTracked(string $name, int $delta, Order $order, string $reason): void
    {
        if ($this->stock->inventoryTracked($name)) {
            $this->stock->adjustInventoryItem($name, $delta, $order, $reason);
        }
    }
}
