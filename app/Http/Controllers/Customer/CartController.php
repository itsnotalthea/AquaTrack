<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\Cart;
use App\Services\OrderPricing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private Cart $cart) {}

    public function index(): JsonResponse
    {
        return response()->json($this->state());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.OrderPricing::MAX_QUANTITY],
            'container_mode' => ['nullable', 'in:exchange,bring_your_own'],
        ]);

        $this->cart->add(
            (int) $data['product_id'],
            (int) $data['quantity'],
            $data['container_mode'] ?? null
        );

        return response()->json($this->state() + ['message' => 'Added to cart.']);
    }

    public function update(Request $request, string $key): JsonResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:'.OrderPricing::MAX_QUANTITY],
        ]);

        $this->cart->update($key, (int) $data['quantity']);

        return response()->json($this->state());
    }

    public function destroy(string $key): JsonResponse
    {
        $this->cart->remove($key);

        return response()->json($this->state());
    }

    public function clear(): JsonResponse
    {
        $this->cart->clear();

        return response()->json($this->state());
    }

    private function state(): array
    {
        return [
            'items' => $this->cart->lines()->map(fn (array $line) => [
                'key' => $line['key'],
                'product_id' => $line['product']->id,
                'name' => $line['product']->name,
                'quantity' => $line['quantity'],
                'container_mode' => $line['container_mode'],
                'unit_price' => $line['unit_price'],
                'subtotal' => $line['subtotal'],
                'available' => $line['available'],
            ])->values(),
            'count' => $this->cart->count(),
            'subtotal' => $this->cart->subtotal(),
            'delivery_fee' => OrderPricing::deliveryFee(),
        ];
    }
}
