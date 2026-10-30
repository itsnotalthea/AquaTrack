<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'unit',
        'fulfillment_type',
        'inventory_item_id',
        'container_mode',
        'quantity',
        'price',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    /**
     * Items created before the fulfillment type existed fall back to the
     * product's unit so old rows and direct test fixtures keep working.
     */
    public function resolvedFulfillmentType(): string
    {
        if ($this->fulfillment_type) {
            return $this->fulfillment_type;
        }

        return match ($this->product?->unit) {
            Product::UNIT_PIECE => Product::FULFILLMENT_NEW_CONTAINER,
            Product::UNIT_MONTH => Product::FULFILLMENT_DISPENSER_RENTAL,
            default => Product::FULFILLMENT_REFILL,
        };
    }

    public function resolvedContainerMode(): ?string
    {
        if ($this->resolvedFulfillmentType() !== Product::FULFILLMENT_REFILL) {
            return null;
        }

        return $this->container_mode ?? Product::CONTAINER_EXCHANGE;
    }

    public function resolvedName(): string
    {
        return $this->product_name ?? $this->product?->name ?? 'Product';
    }

    public function isExchangeRefill(): bool
    {
        return $this->resolvedFulfillmentType() === Product::FULFILLMENT_REFILL
            && $this->resolvedContainerMode() === Product::CONTAINER_EXCHANGE;
    }

    public function isBringYourOwnRefill(): bool
    {
        return $this->resolvedFulfillmentType() === Product::FULFILLMENT_REFILL
            && $this->resolvedContainerMode() === Product::CONTAINER_BRING_YOUR_OWN;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
