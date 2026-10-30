<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ContainerInventory;
use App\Models\ContainerTransaction;
use App\Models\Delivery;
use App\Models\DispenserRental;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Services\OrderFulfillment;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OperationsController extends Controller
{
    public function dashboard(): View
    {
        $containers = ContainerInventory::orderBy('id')->get()->keyBy('status');

        $upcomingDeliveries = Delivery::whereIn('status', [
                Delivery::STATUS_ASSIGNED,
                Delivery::STATUS_IN_TRANSIT,
            ])
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->with('order:id,customer_id,delivery_address,barangay,preferred_date')
            ->get()
            ->groupBy(function ($d) {
                return $d->order?->preferred_date?->format('Y-m-d') ?? $d->created_at->format('Y-m-d');
            })
            ->map(function ($group) {
                return $group->map(function ($d) {
                    return [
                        'order_id' => $d->order_id,
                        'customer' => $d->order?->customer?->name,
                        'barangay' => $d->order?->barangay,
                        'address' => $d->order?->delivery_address,
                        'status' => $d->status,
                    ];
                })->values()->all();
            });

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
            'failedDeliveries' => Order::where('status', Order::STATUS_DELIVERY_FAILED)->count(),
            'activeRentals' => DispenserRental::where('status', DispenserRental::STATUS_ACTIVE)->sum('quantity'),
            'overdueRentals' => DispenserRental::where('status', DispenserRental::STATUS_ACTIVE)
                ->where('due_at', '<', now())->count(),
            'lowStock' => InventoryItem::all()
                ->filter(fn (InventoryItem $item) => $item->isLowStock())
                ->values(),
            'upcomingDeliveries' => $upcomingDeliveries,
        ]);
    }

    public function inventory(): View
    {
        return view('staff.inventory.index', [
            'items' => InventoryItem::orderBy('name')->get(),
        ]);
    }

    public function adjustInventory(Request $request, InventoryItem $item, StockService $stock): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:100000'],
            'reason' => ['required', 'string', 'max:200'],
        ]);

        $delta = (int) $data['quantity'] - (int) $item->quantity;

        try {
            $stock->adjustInventoryItem($item->name, $delta, null, $data['reason'], 'manual');
        } catch (\App\Services\InsufficientStockException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()]);
        }

        return back()->with('status', $item->name.' stock updated.');
    }

    public function containers(): View
    {
        $containers = ContainerInventory::orderBy('id')->get();

        return view('staff.containers.index', [
            'containers' => $containers->keyBy('status'),
            'ordered' => $containers,
            'transactions' => ContainerTransaction::with('customer', 'recorder')
                ->latest()
                ->limit(30)
                ->get(),
        ]);
    }

    public function adjustContainer(Request $request, string $status, StockService $stock): RedirectResponse
    {
        abort_unless(in_array($status, ContainerInventory::STATUSES, true), 404);

        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:100000'],
            'reason' => ['required', 'string', 'max:200'],
        ]);

        $row = ContainerInventory::where('status', $status)->firstOrFail();
        $delta = (int) $data['quantity'] - (int) $row->quantity;

        try {
            $stock->shiftContainer($status, $delta, null, $data['reason']);
        } catch (\App\Services\InsufficientStockException $e) {
            return back()->withErrors(['quantity' => $e->getMessage()]);
        }

        return back()->with('status', ucfirst(str_replace('_', ' ', $status)).' count updated.');
    }

    public function rentals(Request $request): View
    {
        $rentals = DispenserRental::query()
            ->with('customer', 'order', 'product', 'handler')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('staff.rentals.index', [
            'rentals' => $rentals,
            'statuses' => [
                DispenserRental::STATUS_ACTIVE => 'Active',
                DispenserRental::STATUS_OVERDUE => 'Overdue',
                DispenserRental::STATUS_RETURNED => 'Returned',
                DispenserRental::STATUS_DAMAGED => 'Damaged',
                DispenserRental::STATUS_LOST => 'Lost',
            ],
        ]);
    }

    public function returnRental(Request $request, DispenserRental $rental, OrderFulfillment $fulfillment): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:'.$rental->remaining],
            'condition' => ['required', Rule::in([
                DispenserRental::CONDITION_GOOD,
                DispenserRental::CONDITION_DAMAGED,
                DispenserRental::CONDITION_LOST,
            ])],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'quantity.max' => 'You can return at most '.$rental->remaining.' dispenser(s) on this rental.',
        ]);

        $fulfillment->recordDispenserReturn(
            $rental,
            (int) $data['quantity'],
            $data['condition'],
            $data['notes'] ?? null
        );

        return back()->with('status', 'Dispenser return recorded.');
    }
}
