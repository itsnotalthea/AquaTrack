<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PlaceOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    // staff and admin list, filtered like the staff orders page
    public function index(Request $request): JsonResponse
    {
        $orders = Order::query()
            ->with(['customer', 'items.product', 'delivery.driver'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('order_type'), fn ($q) => $q->where('order_type', $request->string('order_type')))
            ->when($request->filled('payment_status'), fn ($q) => $q->where('payment_status', $request->string('payment_status')))
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'status' => $order->status,
                'order_type' => $order->order_type,
                'payment_status' => $order->payment_status,
                'total_amount' => (float) $order->total_amount,
                'customer' => $order->customer?->name,
                'barangay' => $order->barangay,
                'driver' => $order->delivery?->driver?->name,
                'created_at' => $order->created_at->toIso8601String(),
            ]);

        return response()->json(['data' => $orders]);
    }

    public function store(Request $request): JsonResponse
    {
        $order = app(PlaceOrder::class)->handle($request);

        return response()->json([
            'message' => 'Order placed.',
            'order_id' => $order->id,
            'redirect' => route('customer.orders.show', $order),
            'total_amount' => (float) $order->total_amount,
        ], 201);
    }
}
