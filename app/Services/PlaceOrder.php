<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Reminder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Places an order for the authenticated customer. Shared by the web form and
 * the ajax endpoint so validation and pricing live in one place.
 */
class PlaceOrder
{
    public function __construct(private OrderPricing $pricing) {}

    public function handle(Request $request): Order
    {
        $data = $request->validate([
            'product_id' => ['required', Rule::exists('products', 'id')->where('is_active', true)],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.OrderPricing::MAX_QUANTITY],
            'order_type' => ['required', Rule::in([Order::TYPE_DELIVERY, Order::TYPE_PICKUP])],
            'delivery_address' => ['nullable', 'required_if:order_type,'.Order::TYPE_DELIVERY, 'string', 'max:500'],
            'preferred_date' => ['required', 'date', 'after_or_equal:today'],
            'payment_method' => ['required', Rule::in(['cash', 'gcash', 'maya'])],
            'container_swap' => ['boolean'],
            'container_swap_qty' => ['nullable', 'required_if:container_swap,1', 'integer', 'min:1', 'max:'.OrderPricing::MAX_QUANTITY],
            'notes' => ['nullable', 'string', 'max:1000'],
            'weekly_reminder' => ['boolean'],
        ], [
            'quantity.max' => 'You can order at most '.OrderPricing::MAX_QUANTITY.' per product per order.',
            'container_swap_qty.required_if' => 'Enter how many containers you want to swap.',
            'delivery_address.required_if' => 'A delivery address is required for delivery orders.',
        ]);

        $product = Product::findOrFail($data['product_id']);
        $quantity = (int) $data['quantity'];
        $swap = (bool) ($data['container_swap'] ?? false);

        $order = DB::transaction(function () use ($request, $data, $product, $quantity, $swap) {
            $order = $request->user()->orders()->create([
                'order_type' => $data['order_type'],
                'status' => Order::STATUS_PENDING,
                'total_amount' => $this->pricing->total((float) $product->price, $quantity, $data['order_type']),
                'payment_status' => 'unpaid',
                'payment_method' => $data['payment_method'],
                'delivery_address' => $data['delivery_address'] ?? null,
                'barangay' => $request->user()->barangay,
                'preferred_date' => $data['preferred_date'],
                'notes' => $data['notes'] ?? null,
                'container_swap' => $swap,
                'container_swap_qty' => $swap ? (int) $data['container_swap_qty'] : null,
                'weekly_reminder' => (bool) ($data['weekly_reminder'] ?? false),
            ]);

            $order->items()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $product->price,
                'subtotal' => $this->pricing->subtotal((float) $product->price, $quantity),
            ]);

            return $order;
        });

        $this->syncReminder($request, (bool) ($data['weekly_reminder'] ?? false));

        return $order;
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
