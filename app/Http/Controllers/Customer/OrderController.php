<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Services\Cart;
use App\Services\InsufficientStockException;
use App\Services\OrderPricing;
use App\Services\PlaceOrder;
use App\Services\StockService;
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

    public function create(Request $request, Cart $cart, StockService $stock): View
    {
        $products = Product::active()->orderBy('name')->get()
            ->each(function (Product $product) use ($stock) {
                $product->setAttribute('available', $stock->availableFor($product, Product::CONTAINER_EXCHANGE));
            });

        return view('customer.orders.create', [
            'products' => $products,
            'deliveryFee' => OrderPricing::deliveryFee(),
            'maxQuantity' => OrderPricing::MAX_QUANTITY,
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
}
