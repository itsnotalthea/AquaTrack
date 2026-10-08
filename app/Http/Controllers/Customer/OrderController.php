<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderPricing;
use App\Services\PlaceOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()
            ->orders()
            ->with('items.product')
            ->latest()
            ->paginate(10);

        return view('customer.orders.index', ['orders' => $orders]);
    }

    public function create(): View
    {
        return view('customer.orders.create', [
            'products' => Product::active()->orderBy('name')->get(),
            'deliveryFee' => OrderPricing::deliveryFee(),
            'maxQuantity' => OrderPricing::MAX_QUANTITY,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $order = app(PlaceOrder::class)->handle($request);

        return redirect()
            ->route('customer.orders.show', $order)
            ->with('status', 'Order placed.');
    }

    public function show(Request $request, Order $order): View
    {
        abort_if($order->customer_id !== $request->user()->id, 404);

        $order->load('items.product');

        return view('customer.orders.show', ['order' => $order]);
    }
}
