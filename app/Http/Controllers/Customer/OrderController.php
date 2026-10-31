<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\Order;
use App\Models\Product;
use App\Services\Cart;
use App\Services\InsufficientStockException;
use App\Services\OrderPricing;
use App\Services\PlaceOrder;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private StockService $stock) {}

    public function index(Request $request): View
    {
        $orders = $request->user()
            ->orders()
            ->with('items.product')
            ->latest()
            ->paginate(10);

        return view('customer.orders.index', ['orders' => $orders]);
    }

    public function create(Request $request, Cart $cart, StockService $stock): View
    {
        $products = Product::active()->orderBy('name')->get()
            ->each(function (Product $product) use ($stock) {
                $product->setAttribute('available', $stock->availableFor($product, Product::CONTAINER_EXCHANGE));
            });

        return view('customer.orders.create', [
            'products' => $products,
            'deliveryFee' => OrderPricing::deliveryFee(),
            'cartLines' => $cart->lines(),
            'cartSubtotal' => $cart->subtotal(),
            'cartCount' => $cart->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $order = app(PlaceOrder::class)->handle($request);
        } catch (InsufficientStockException $e) {
            return back()->withInput()->withErrors(['stock' => $e->getMessage()]);
        }

        return redirect()
            ->route('customer.orders.show', $order)
            ->with('status', 'Order placed.');
    }

    public function show(Request $request, Order $order): View
    {
        abort_if($order->customer_id !== $request->user()->id, 404);

        $order->load('items.product', 'delivery.driver', 'dispenserRentals', 'statusLogs');

        return view('customer.orders.show', ['order' => $order]);
    }

    public function edit(Request $request, Order $order): View
    {
        abort_if($order->customer_id !== $request->user()->id, 404);
        abort_unless($order->status === Order::STATUS_PENDING, 403);

        $order->load('items.product');
        $editItems = old('items');
        if (! is_array($editItems)) {
            $editItems = $order->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'container_mode' => $item->resolvedContainerMode(),
            ])->all();
        } else {
            $editItems = array_values($editItems);
        }

        $existingProductIds = $order->items->pluck('product_id');
        $products = Product::query()
            ->where(function ($query) use ($existingProductIds) {
                $query->where('is_active', true)
                    ->orWhereIn('id', $existingProductIds);
            })
            ->orderBy('name')
            ->get();

        return view('customer.orders.edit', [
            'order' => $order,
            'products' => $products,
            'barangays' => Barangay::districts(),
            'editItems' => $editItems,
        ]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        abort_if($order->customer_id !== $request->user()->id, 404);

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.container_mode' => ['nullable', 'in:exchange,bring_your_own'],
            'order_type' => ['required', 'in:delivery,pickup'],
            'delivery_address' => ['nullable', 'required_if:order_type,delivery', 'string', 'max:500'],
            'barangay' => ['nullable', 'string', Rule::in(Barangay::names())],
            'preferred_date' => ['required', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $order->load('items.product');
        $existingProductIds = $order->items->pluck('product_id')->map(fn ($id) => (int) $id)->all();
        $products = Product::whereIn('id', collect($data['items'])->pluck('product_id'))->get()->keyBy('id');
        $lines = [];

        foreach ($data['items'] as $index => $item) {
            $product = $products->get($item['product_id']);
            if (! $product || (! $product->is_active && ! in_array((int) $product->id, $existingProductIds, true))) {
                throw ValidationException::withMessages([
                    "items.$index.product_id" => 'Choose an active product.',
                ]);
            }

            $mode = $product->isRefill()
                ? ($item['container_mode'] ?? Product::CONTAINER_EXCHANGE)
                : null;
            $key = $product->id.'|'.($mode ?? '');

            if (isset($lines[$key])) {
                $lines[$key]['quantity'] += (int) $item['quantity'];
            } else {
                $lines[$key] = [
                    'product' => $product,
                    'quantity' => (int) $item['quantity'],
                    'container_mode' => $mode,
                ];
            }
        }

        $lines = array_values($lines);
        $oldQuantities = $order->items->mapWithKeys(fn ($item) => [
            $item->product_id.'|'.($item->resolvedContainerMode() ?? '') => (int) $item->quantity,
        ]);
        $increasedLines = [];

        foreach ($lines as $line) {
            $key = $line['product']->id.'|'.($line['container_mode'] ?? '');
            $increase = $line['quantity'] - (int) $oldQuantities->get($key, 0);
            if ($increase > 0) {
                $increasedLines[] = array_merge($line, ['quantity' => $increase]);
            }
        }

        try {
            $this->stock->assertAvailable($increasedLines);
        } catch (InsufficientStockException $exception) {
            return back()->withInput()->withErrors(['stock' => $exception->getMessage()]);
        }

        $subtotalLines = array_map(fn (array $line) => [
            'price' => $line['product']->price,
            'quantity' => $line['quantity'],
        ], $lines);
        $pricing = OrderPricing::totals($subtotalLines, $data['order_type']);
        $exchangeQuantity = collect($lines)
            ->filter(fn (array $line) => $line['product']->isRefill()
                && $line['container_mode'] === Product::CONTAINER_EXCHANGE)
            ->sum('quantity');

        DB::transaction(function () use ($request, $order, $data, $lines, $pricing, $exchangeQuantity) {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless(
                $lockedOrder->customer_id === $request->user()->id
                    && $lockedOrder->status === Order::STATUS_PENDING,
                403
            );

            $beforeItems = $lockedOrder->items()->with('product')->get()
                ->map(fn ($item) => $item->resolvedName().' × '.$item->quantity.($item->resolvedContainerMode()
                    ? ' ('.str_replace('_', ' ', $item->resolvedContainerMode()).')'
                    : ''))
                ->implode(', ');
            $before = [
                'order_type' => $lockedOrder->order_type,
                'delivery_address' => $lockedOrder->delivery_address,
                'barangay' => $lockedOrder->barangay,
                'preferred_date' => $lockedOrder->preferred_date?->format('Y-m-d'),
                'notes' => $lockedOrder->notes,
                'items' => $beforeItems,
            ];

            $lockedOrder->update([
                'order_type' => $data['order_type'],
                'subtotal' => $pricing['subtotal'],
                'delivery_fee' => $pricing['delivery_fee'],
                'total_amount' => $pricing['total'],
                'delivery_address' => $data['order_type'] === Order::TYPE_DELIVERY ? ($data['delivery_address'] ?? null) : null,
                'barangay' => $data['barangay'] ?? null,
                'preferred_date' => $data['preferred_date'],
                'notes' => $data['notes'] ?? null,
                'container_swap' => $exchangeQuantity > 0,
                'container_swap_qty' => $exchangeQuantity > 0 ? $exchangeQuantity : null,
            ]);

            $lockedOrder->items()->delete();
            foreach ($lines as $line) {
                $product = $line['product'];
                $lockedOrder->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit' => $product->unit,
                    'fulfillment_type' => $product->resolvedFulfillmentType(),
                    'inventory_item_id' => $product->inventory_item_id,
                    'container_mode' => $line['container_mode'],
                    'quantity' => $line['quantity'],
                    'price' => $product->price,
                    'subtotal' => OrderPricing::subtotal((float) $product->price, $line['quantity']),
                ]);
            }

            $afterItems = collect($lines)
                ->map(fn (array $line) => $line['product']->name.' × '.$line['quantity'].($line['container_mode']
                    ? ' ('.str_replace('_', ' ', $line['container_mode']).')'
                    : ''))
                ->implode(', ');
            $after = [
                'order_type' => $data['order_type'],
                'delivery_address' => $data['order_type'] === Order::TYPE_DELIVERY ? ($data['delivery_address'] ?? null) : null,
                'barangay' => $data['barangay'] ?? null,
                'preferred_date' => $data['preferred_date'],
                'notes' => $data['notes'] ?? null,
                'items' => $afterItems,
            ];
            $labels = [
                'order_type' => 'Order type',
                'delivery_address' => 'Delivery address',
                'barangay' => 'Barangay',
                'preferred_date' => 'Preferred date',
                'notes' => 'Notes',
                'items' => 'Items',
            ];
            $changes = [];

            foreach ($labels as $key => $label) {
                $oldValue = (string) ($before[$key] ?? '');
                $newValue = (string) ($after[$key] ?? '');
                if ($oldValue !== $newValue) {
                    $changes[$label] = ['old' => $oldValue ?: '—', 'new' => $newValue ?: '—'];
                }
            }

            if ($changes !== []) {
                $lockedOrder->changeLogs()->create([
                    'user_id' => $request->user()->id,
                    'changes' => $changes,
                ]);
            }
        });

        return redirect()
            ->route('customer.orders.show', $order)
            ->with('status', 'Order details updated.');
    }
}
