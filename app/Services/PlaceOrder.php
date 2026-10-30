<?php

namespace App\Services;

use App\Models\Barangay;
use App\Models\Order;
use App\Models\Product;
use App\Models\Reminder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Places an order for the authenticated customer. Shared by the web form,
 * the cart checkout and the ajax endpoint so validation and pricing live in
 * exactly one place.
 */
class PlaceOrder
{
    public function __construct(
        private OrderPricing $pricing,
        private StockService $stock,
        private Cart $cart,
    ) {}

    public function handle(Request $request): Order
    {
        $this->mergeCartIfRequested($request);

        $data = $request->validate([
            'items' => ['required_without:product_id', 'array', 'min:1', 'max:20'],
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->where('is_active', true)],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:'.OrderPricing::MAX_QUANTITY],
            'items.*.container_mode' => ['nullable', Rule::in([Product::CONTAINER_EXCHANGE, Product::CONTAINER_BRING_YOUR_OWN])],
            'product_id' => ['required_without:items', Rule::exists('products', 'id')->where('is_active', true)],
            'quantity' => ['required_without:items', 'integer', 'min:1', 'max:'.OrderPricing::MAX_QUANTITY],
            'order_type' => ['required', Rule::in([Order::TYPE_DELIVERY, Order::TYPE_PICKUP])],
            'delivery_address' => ['nullable', 'required_if:order_type,'.Order::TYPE_DELIVERY, 'string', 'max:500'],
            'barangay' => ['nullable', 'string', Rule::in(Barangay::names())],
            'preferred_date' => ['required', 'date', 'after_or_equal:today'],
            'payment_method' => ['required', Rule::in(['cash', 'gcash', 'maya'])],
            'payment_reference' => ['nullable', 'required_if:payment_method,gcash', 'required_if:payment_method,maya', 'string', 'max:120'],
            'payment_proof' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'container_swap' => ['boolean'],
            'container_swap_qty' => ['nullable', 'required_if:container_swap,1', 'integer', 'min:1', 'max:'.(OrderPricing::MAX_QUANTITY * 4)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'weekly_reminder' => ['boolean'],
            'use_cart' => ['boolean'],
        ], [
            'items.required_without' => 'Add at least one product before checking out.',
            'items.*.quantity.max' => 'You can order at most '.OrderPricing::MAX_QUANTITY.' per product per order.',
            'quantity.max' => 'You can order at most '.OrderPricing::MAX_QUANTITY.' per product per order.',
            'container_swap_qty.required_if' => 'Enter how many containers you want to swap.',
            'delivery_address.required_if' => 'A delivery address is required for delivery orders.',
        ]);

        $items = $data['items'] ?? [[
            'product_id' => $data['product_id'],
            'quantity' => $data['quantity'],
            'container_mode' => ($data['container_swap'] ?? false)
                ? Product::CONTAINER_EXCHANGE
                : Product::CONTAINER_BRING_YOUR_OWN,
        ]];

        $lines = $this->buildLines($items);
        $this->stock->assertAvailable($lines);

        $orderType = $data['order_type'];
        $pricing = OrderPricing::totals(array_map(fn (array $line) => [
            'price' => $line['product']->price,
            'quantity' => $line['quantity'],
        ], $lines), $orderType);
        $exchangeQuantity = collect($lines)
            ->filter(fn (array $line) => $line['product']->isRefill()
                && $line['container_mode'] === Product::CONTAINER_EXCHANGE)
            ->sum('quantity');

        $order = DB::transaction(function () use ($request, $data, $lines, $pricing, $exchangeQuantity) {
            $swapQty = ($data['container_swap'] ?? false) && ! empty($data['container_swap_qty'])
                ? (int) $data['container_swap_qty']
                : (int) $exchangeQuantity;

            $proofPath = null;
            if ($data['payment_method'] !== 'cash' && $request->hasFile('payment_proof')) {
                $proofPath = $request->file('payment_proof')->store('payment-proofs', 'public');
            }

            $order = $request->user()->orders()->create([
                'order_type' => $data['order_type'],
                'status' => Order::STATUS_PENDING,
                'subtotal' => $pricing['subtotal'],
                'delivery_fee' => $pricing['delivery_fee'],
                'total_amount' => $pricing['total'],
                'payment_status' => 'unpaid',
                'payment_method' => $data['payment_method'],
                'payment_reference' => $data['payment_method'] === 'cash' ? null : ($data['payment_reference'] ?? null),
                'payment_proof_path' => $proofPath,
                'delivery_address' => $data['delivery_address'] ?? null,
                'barangay' => $data['barangay'] ?? $request->user()->barangay,
                'preferred_date' => $data['preferred_date'],
                'notes' => $data['notes'] ?? null,
                'container_swap' => $swapQty > 0,
                'container_swap_qty' => $swapQty > 0 ? $swapQty : null,
                'weekly_reminder' => (bool) ($data['weekly_reminder'] ?? false),
            ]);

            foreach ($lines as $line) {
                $product = $line['product'];

                $order->items()->create([
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

            return $order;
        });

        $this->syncReminder($request, (bool) ($data['weekly_reminder'] ?? false));

        if ($request->boolean('use_cart')) {
            $this->cart->clear();
        }

        return $order;
    }

    /**
     * When checkout comes from the cart drawer, pull the session lines into
     * the request so the normal multi-item pipeline handles them.
     */
    private function mergeCartIfRequested(Request $request): void
    {
        if ($request->filled('items') || ! $request->boolean('use_cart')) {
            return;
        }

        if ($this->cart->lines()->isNotEmpty()) {
            $request->merge(['items' => $this->cart->toOrderItems()]);
        }
    }

    /**
     * @param  array<int, array{product_id: int|string, quantity: int|string, container_mode?: ?string}>  $items
     * @return array<int, array{product: Product, quantity: int, container_mode: ?string}>
     */
    private function buildLines(array $items): array
    {
        $products = Product::whereIn('id', collect($items)->pluck('product_id'))->get()->keyBy('id');
        $lines = [];

        foreach ($items as $index => $item) {
            $product = $products->get($item['product_id']);

            if (! $product) {
                throw ValidationException::withMessages([
                    "items.$index.product_id" => 'That product is no longer available.',
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

        foreach ($lines as $line) {
            if ($line['quantity'] > OrderPricing::MAX_QUANTITY) {
                throw ValidationException::withMessages([
                    'items' => 'You can order at most '.OrderPricing::MAX_QUANTITY.' of '.$line['product']->name.' per order.',
                ]);
            }
        }

        return array_values($lines);
    }

    // opt-in keeps the reminders row in sync with the customer's choice
    private function syncReminder(Request $request, bool $optedIn): void
    {
        if ($optedIn) {
            Reminder::updateOrCreate(
                ['customer_id' => $request->user()->id],
                ['email' => $request->user()->email, 'frequency' => 'weekly', 'is_active' => true]
            );

            return;
        }

        Reminder::where('customer_id', $request->user()->id)->update(['is_active' => false]);
    }
}
