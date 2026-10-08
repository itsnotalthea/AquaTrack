<?php

namespace App\Services;

use App\Models\Barangay;
use App\Models\ContainerInventory;
use App\Models\ContainerTransaction;
use App\Models\Delivery;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportBuilder
{
    public const STATUS_FILTERS = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'preparing' => 'Preparing',
        'out_for_delivery' => 'Out for delivery',
        'delivered' => 'Delivered',
        'ready_for_pickup' => 'Ready for pickup',
        'picked_up' => 'Picked up',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    public function __construct(private string $type, private Request $request) {}

    public function filters(): array
    {
        return [
            'from' => $this->request->input('from'),
            'to' => $this->request->input('to'),
            'status' => $this->request->input('status'),
            'payment_status' => $this->request->input('payment_status'),
            'payment_method' => $this->request->input('payment_method'),
            'order_type' => $this->request->input('order_type'),
            'driver_id' => $this->request->input('driver_id'),
            'product_id' => $this->request->input('product_id'),
            'customer_id' => $this->request->input('customer_id'),
            'barangay' => $this->request->input('barangay'),
            'container_status' => $this->request->input('container_status'),
            'low_stock' => $this->request->boolean('low_stock'),
        ];
    }

    public function options(): array
    {
        return [
            'statuses' => self::STATUS_FILTERS,
            'paymentStatuses' => ['unpaid' => 'Unpaid', 'paid' => 'Paid'],
            'paymentMethods' => ['cash' => 'Cash', 'gcash' => 'GCash', 'maya' => 'Maya'],
            'orderTypes' => ['delivery' => 'Delivery', 'pickup' => 'Pickup'],
            'products' => Product::orderBy('name')->pluck('name', 'id')->all(),
            'drivers' => User::where('role', User::ROLE_DRIVER)->orderBy('last_name')
                ->get()->mapWithKeys(fn (User $u) => [$u->id => $u->name])->all(),
            'customers' => User::where('role', User::ROLE_CUSTOMER)->orderBy('last_name')
                ->get()->mapWithKeys(fn (User $u) => [$u->id => $u->name.' ('.$u->email.')'])->all(),
            'barangays' => Barangay::names(),
            'containerStatuses' => array_combine(ContainerInventory::STATUSES, ContainerInventory::STATUSES),
            'deliveryStatuses' => [
                Delivery::STATUS_ASSIGNED => 'Assigned',
                Delivery::STATUS_IN_TRANSIT => 'In transit',
                Delivery::STATUS_DELIVERED => 'Delivered',
                Delivery::STATUS_RETURNED_TO_STATION => 'Returned to station',
            ],
        ];
    }

    public function build(): array
    {
        return match ($this->type) {
            'sales' => $this->sales(),
            'sales_by_product' => $this->salesByProduct(),
            'payments' => $this->payments(),
            'orders' => $this->orders(),
            'deliveries' => $this->deliveries(),
            'containers' => $this->containers(),
            'inventory' => $this->inventory(),
            'customers' => $this->customers(),
            default => ['columns' => [], 'rows' => [], 'totals' => []],
        };
    }

    // shared order scope, every order based report starts from here
    private function baseOrderQuery()
    {
        $f = $this->filters();

        return Order::query()
            ->when($f['from'], fn ($q) => $q->whereDate('created_at', '>=', $f['from']))
            ->when($f['to'], fn ($q) => $q->whereDate('created_at', '<=', $f['to']))
            ->when($f['status'], fn ($q) => $q->where('status', $f['status']))
            ->when($f['payment_status'], fn ($q) => $q->where('payment_status', $f['payment_status']))
            ->when($f['payment_method'], fn ($q) => $q->where('payment_method', $f['payment_method']))
            ->when($f['order_type'], fn ($q) => $q->where('order_type', $f['order_type']))
            ->when($f['customer_id'], fn ($q) => $q->where('customer_id', $f['customer_id']))
            ->when($f['barangay'], fn ($q) => $q->where('barangay', $f['barangay']))
            ->when($f['product_id'], fn ($q) => $q->whereHas('items', fn ($i) => $i->where('product_id', $f['product_id'])))
            ->when($f['driver_id'], fn ($q) => $q->whereHas('delivery', fn ($d) => $d->where('driver_id', $f['driver_id'])));
    }

    private function sales(): array
    {
        $rows = $this->baseOrderQuery()
            ->whereNot('status', Order::STATUS_CANCELLED)
            ->with('customer', 'items.product')
            ->orderByDesc('created_at')
            ->limit(500)
            ->get()
            ->map(fn (Order $o) => [
                'id' => $o->id,
                'date' => $o->created_at->format('Y-m-d H:i'),
                'customer' => $o->customer?->name,
                'type' => $o->order_type,
                'items' => $o->items->map(fn ($i) => $i->quantity.'x '.$i->product->name)->implode(', '),
                'total' => (float) $o->total_amount,
                'payment' => $o->payment_status,
            ])->all();

        $gross = array_sum(array_column($rows, 'total'));
        $collected = 0.0;
        foreach ($rows as $row) {
            if ($row['payment'] === 'paid') {
                $collected += $row['total'];
            }
        }

        return [
            'columns' => ['id' => 'Order', 'date' => 'Date', 'customer' => 'Customer', 'type' => 'Type', 'items' => 'Items', 'total' => 'Total', 'payment' => 'Payment'],
            'rows' => $rows,
            'totals' => ['gross' => $gross, 'collected' => $collected, 'orders' => count($rows)],
        ];
    }

    private function salesByProduct(): array
    {
        $f = $this->filters();

        $rows = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->when($f['from'], fn ($q) => $q->whereDate('orders.created_at', '>=', $f['from']))
            ->when($f['to'], fn ($q) => $q->whereDate('orders.created_at', '<=', $f['to']))
            ->when($f['product_id'], fn ($q) => $q->where('order_items.product_id', $f['product_id']))
            ->where('orders.status', '!=', Order::STATUS_CANCELLED)
            ->groupBy('products.id', 'products.name', 'products.unit')
            ->orderByDesc(DB::raw('SUM(order_items.subtotal)'))
            ->select('products.name', 'products.unit', DB::raw('SUM(order_items.quantity) as qty'), DB::raw('SUM(order_items.subtotal) as amount'))
            ->get()
            ->map(fn ($r) => [
                'name' => $r->name,
                'unit' => $r->unit,
                'qty' => (int) $r->qty,
                'amount' => (float) $r->amount,
            ])->all();

        return [
            'columns' => ['name' => 'Product', 'unit' => 'Unit', 'qty' => 'Quantity', 'amount' => 'Amount'],
            'rows' => $rows,
            'totals' => ['qty' => array_sum(array_column($rows, 'qty')), 'amount' => array_sum(array_column($rows, 'amount'))],
        ];
    }

    private function payments(): array
    {
        $f = $this->filters();

        $rows = Payment::query()
            ->with('order', 'recorder')
            ->when($f['from'], fn ($q) => $q->whereDate('payments.created_at', '>=', $f['from']))
            ->when($f['to'], fn ($q) => $q->whereDate('payments.created_at', '<=', $f['to']))
            ->when($f['payment_method'], fn ($q) => $q->where('method', $f['payment_method']))
            ->orderByDesc('payments.created_at')
            ->limit(500)
            ->get()
            ->map(fn (Payment $p) => [
                'date' => $p->created_at->format('Y-m-d H:i'),
                'order' => $p->order_id,
                'method' => $p->method,
                'amount' => (float) $p->amount,
                'by' => $p->recorder?->name,
            ])->all();

        return [
            'columns' => ['date' => 'Date', 'order' => 'Order', 'method' => 'Method', 'amount' => 'Amount', 'by' => 'Recorded by'],
            'rows' => $rows,
            'totals' => ['amount' => array_sum(array_column($rows, 'amount')), 'count' => count($rows)],
        ];
    }

    private function orders(): array
    {
        $rows = $this->baseOrderQuery()->with('customer', 'items.product')
            ->orderByDesc('created_at')->limit(500)->get()
            ->map(fn (Order $o) => [
                'id' => $o->id,
                'date' => $o->created_at->format('Y-m-d H:i'),
                'customer' => $o->customer?->name,
                'type' => $o->order_type,
                'barangay' => $o->barangay,
                'status' => $o->status,
                'payment' => $o->payment_status,
                'total' => (float) $o->total_amount,
            ])->all();

        return [
            'columns' => ['id' => 'Order', 'date' => 'Date', 'customer' => 'Customer', 'type' => 'Type', 'barangay' => 'Barangay', 'status' => 'Status', 'payment' => 'Payment', 'total' => 'Total'],
            'rows' => $rows,
            'totals' => ['total' => array_sum(array_column($rows, 'total')), 'count' => count($rows)],
        ];
    }

    private function deliveries(): array
    {
        $f = $this->filters();

        $rows = Delivery::query()
            ->with('order.customer', 'driver')
            ->when($f['from'], fn ($q) => $q->whereDate('created_at', '>=', $f['from']))
            ->when($f['to'], fn ($q) => $q->whereDate('created_at', '<=', $f['to']))
            ->when($f['driver_id'], fn ($q) => $q->where('driver_id', $f['driver_id']))
            ->when($f['status'], fn ($q) => $q->where('status', $f['status']))
            ->orderByDesc('created_at')->limit(500)->get()
            ->map(fn (Delivery $d) => [
                'order' => $d->order_id,
                'customer' => $d->order?->customer?->name,
                'barangay' => $d->order?->barangay,
                'driver' => $d->driver?->name,
                'status' => $d->status,
                'delivered_at' => $d->delivered_at?->format('Y-m-d H:i'),
            ])->all();

        return [
            'columns' => ['order' => 'Order', 'customer' => 'Customer', 'barangay' => 'Barangay', 'driver' => 'Driver', 'status' => 'Status', 'delivered_at' => 'Delivered at'],
            'rows' => $rows,
            'totals' => ['count' => count($rows)],
        ];
    }

    private function containers(): array
    {
        $f = $this->filters();

        $rows = ContainerInventory::orderBy('id')->get()
            ->map(fn (ContainerInventory $c) => [
                'status' => $c->status,
                'quantity' => (int) $c->quantity,
            ])->all();

        $transactions = ContainerTransaction::with('customer')
            ->when($f['from'], fn ($q) => $q->whereDate('created_at', '>=', $f['from']))
            ->when($f['to'], fn ($q) => $q->whereDate('created_at', '<=', $f['to']))
            ->when($f['customer_id'], fn ($q) => $q->where('customer_id', $f['customer_id']))
            ->orderByDesc('created_at')->limit(200)->get()
            ->map(fn (ContainerTransaction $t) => [
                'date' => $t->created_at->format('Y-m-d'),
                'customer' => $t->customer?->name,
                'type' => $t->type,
                'quantity' => (int) $t->quantity,
                'order' => $t->order_id,
            ])->all();

        return [
            'columns' => ['status' => 'Container status', 'quantity' => 'Count'],
            'rows' => $rows,
            'transactions' => $transactions,
            'totals' => ['total' => array_sum(array_column($rows, 'quantity'))],
        ];
    }

    private function inventory(): array
    {
        $f = $this->filters();

        $items = InventoryItem::orderBy('name')->get();

        if ($f['low_stock']) {
            $items = $items->filter(fn (InventoryItem $i) => $i->isLowStock())->values();
        }

        if ($f['container_status']) {
            $rows = ContainerInventory::where('status', $f['container_status'])->get()
                ->map(fn (ContainerInventory $c) => [
                    'name' => str_replace('_', ' ', $c->status),
                    'quantity' => (int) $c->quantity,
                    'threshold' => '-',
                    'flag' => '',
                ])->all();

            return [
                'columns' => ['name' => 'Item', 'quantity' => 'Quantity', 'threshold' => 'Threshold', 'flag' => 'Status'],
                'rows' => $rows,
                'totals' => ['count' => count($rows)],
            ];
        }

        $rows = $items->map(fn (InventoryItem $i) => [
            'name' => str_replace('_', ' ', $i->name),
            'quantity' => (int) $i->quantity,
            'threshold' => (int) $i->threshold,
            'flag' => $i->isLowStock() ? 'Low stock' : 'OK',
        ])->all();

        return [
            'columns' => ['name' => 'Item', 'quantity' => 'Quantity', 'threshold' => 'Threshold', 'flag' => 'Status'],
            'rows' => $rows,
            'totals' => ['count' => count($rows)],
        ];
    }

    private function customers(): array
    {
        $f = $this->filters();

        $rows = User::where('role', User::ROLE_CUSTOMER)
            ->when($f['barangay'], fn ($q) => $q->where('barangay', $f['barangay']))
            ->when($f['customer_id'], fn ($q) => $q->whereKey($f['customer_id']))
            ->orderBy('last_name')
            ->limit(500)
            ->get()
            ->map(function (User $u) use ($f) {
                $query = Order::where('customer_id', $u->id)->whereNot('status', Order::STATUS_CANCELLED);
                if ($f['from']) {
                    $query->whereDate('created_at', '>=', $f['from']);
                }
                if ($f['to']) {
                    $query->whereDate('created_at', '<=', $f['to']);
                }

                return [
                    'name' => $u->name,
                    'email' => $u->email,
                    'mobile' => $u->mobile,
                    'barangay' => $u->barangay,
                    'orders' => (clone $query)->count(),
                    'spent' => (float) (clone $query)->sum('total_amount'),
                ];
            })->all();

        return [
            'columns' => ['name' => 'Customer', 'email' => 'Email', 'mobile' => 'Mobile', 'barangay' => 'Barangay', 'orders' => 'Orders', 'spent' => 'Total spent'],
            'rows' => $rows,
            'totals' => ['customers' => count($rows), 'spent' => array_sum(array_column($rows, 'spent'))],
        ];
    }
}
