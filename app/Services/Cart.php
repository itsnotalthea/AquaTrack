<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Session-backed cart. Only the product id, quantity and container mode are
 * trusted from the session; prices and names are always re-read from the
 * database so a stale session can never set its own price.
 */
class Cart
{
    private const KEY = 'cart.items';

    public function __construct(private StockService $stock) {}

    /**
     * @return Collection<int, array{key: string, product: Product, quantity: int, container_mode: ?string, unit_price: float, subtotal: float, available: int}>
     */
    public function lines(): Collection
    {
        $raw = session(self::KEY, []);

        if (! is_array($raw) || $raw === []) {
            return collect();
        }

        $products = Product::whereIn('id', collect($raw)->pluck('product_id'))
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        return collect($raw)
            ->map(function (array $item, string $key) use ($products) {
                $product = $products->get($item['product_id'] ?? null);

                if (! $product) {
                    return null;
                }

                $quantity = max(1, (int) ($item['quantity'] ?? 1));
                $mode = $product->isRefill()
                    ? ($item['container_mode'] ?? Product::CONTAINER_EXCHANGE)
                    : null;

                return [
                    'key' => $key,
                    'product' => $product,
                    'quantity' => $quantity,
                    'container_mode' => $mode,
                    'unit_price' => (float) $product->price,
                    'subtotal' => round((float) $product->price * $quantity, 2),
                    'available' => $this->stock->availableFor($product, $mode),
                ];
            })
            ->filter()
            ->values();
    }

    public function add(int $productId, int $quantity, ?string $containerMode): void
    {
        $product = Product::active()->find($productId);

        if (! $product) {
            throw ValidationException::withMessages(['cart' => 'That product is no longer available.']);
        }

        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Choose at least 1 unit.',
            ]);
        }

        $mode = $product->isRefill()
            ? (in_array($containerMode, [Product::CONTAINER_EXCHANGE, Product::CONTAINER_BRING_YOUR_OWN], true)
                ? $containerMode
                : Product::CONTAINER_EXCHANGE)
            : null;

        $key = $product->id.'|'.($mode ?? '');
        $items = session(self::KEY, []);
        $existing = (int) ($items[$key]['quantity'] ?? 0);
        $next = $existing + $quantity;

        $available = $this->stock->availableFor($product, $mode);

        if ($next > $available) {
            throw ValidationException::withMessages([
                'quantity' => "Only {$available} of {$product->name} available right now.",
            ]);
        }

        $items[$key] = [
            'product_id' => $product->id,
            'quantity' => $next,
            'container_mode' => $mode,
        ];

        session([self::KEY => $items]);
    }

    public function update(string $key, int $quantity): void
    {
        $items = session(self::KEY, []);

        if (! isset($items[$key])) {
            return;
        }

        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'Choose at least 1 unit.',
            ]);
        }

        $product = Product::find($items[$key]['product_id']);
        $available = $product
            ? $this->stock->availableFor($product, $items[$key]['container_mode'] ?? null)
            : 0;

        if ($quantity > $available) {
            throw ValidationException::withMessages([
                'quantity' => "Only {$available} available right now.",
            ]);
        }

        $items[$key]['quantity'] = $quantity;
        session([self::KEY => $items]);
    }

    public function remove(string $key): void
    {
        $items = session(self::KEY, []);
        unset($items[$key]);
        session([self::KEY => $items]);
    }

    public function clear(): void
    {
        session()->forget(self::KEY);
    }

    public function count(): int
    {
        return (int) $this->lines()->sum('quantity');
    }

    public function subtotal(): float
    {
        return round((float) $this->lines()->sum('subtotal'), 2);
    }

    /**
     * @return array<int, array{product_id: int, quantity: int, container_mode: ?string}>
     */
    public function toOrderItems(): array
    {
        return $this->lines()->map(fn (array $line) => [
            'product_id' => $line['product']->id,
            'quantity' => $line['quantity'],
            'container_mode' => $line['container_mode'],
        ])->all();
    }
}
