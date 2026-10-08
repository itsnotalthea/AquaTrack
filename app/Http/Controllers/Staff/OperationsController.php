<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ContainerInventory;
use App\Models\Delivery;
use App\Models\InventoryItem;
use App\Models\Order;
use Illuminate\View\View;

class OperationsController extends Controller
{
    public function dashboard(): View
    {
        $containers = ContainerInventory::orderBy('id')->get()->keyBy('status');

        return view('staff.dashboard', [
            'containers' => $containers,
            'todaysOrders' => Order::whereDate('created_at', today())->count(),
            'pendingOrders' => Order::where('status', Order::STATUS_PENDING)->count(),
            'awaitingPayment' => Order::whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_PICKED_UP])
                ->where('payment_status', 'unpaid')
                ->count(),
            'activeDeliveries' => Delivery::whereIn('status', [
                Delivery::STATUS_ASSIGNED,
                Delivery::STATUS_IN_TRANSIT,
            ])->count(),
            'lowStock' => InventoryItem::all()
                ->filter(fn (InventoryItem $item) => $item->isLowStock())
                ->values(),
        ]);
    }

    public function inventory(): View
    {
        return view('staff.inventory.index', [
            'items' => InventoryItem::orderBy('name')->get(),
        ]);
    }

    public function containers(): View
    {
        $containers = ContainerInventory::orderBy('id')->get();

        return view('staff.containers.index', [
            'containers' => $containers->keyBy('status'),
            'ordered' => $containers,
        ]);
    }
}
