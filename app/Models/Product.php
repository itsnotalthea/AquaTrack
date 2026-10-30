<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    public const UNIT_GALLON = 'gallon';

    public const UNIT_PIECE = 'piece';

    public const UNIT_MONTH = 'month';

    public const FULFILLMENT_REFILL = 'refill';

    public const FULFILLMENT_NEW_CONTAINER = 'new_container';

    public const FULFILLMENT_DISPENSER_RENTAL = 'dispenser_rental';

    public const FULFILLMENT_STOCKED_ITEM = 'stocked_item';

    public const CONTAINER_EXCHANGE = 'exchange';

    public const CONTAINER_BRING_YOUR_OWN = 'bring_your_own';

    protected $fillable = [
        'name',
        'description',
        'price',
        'unit',
        'fulfillment_type',
        'inventory_item_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function inventoryItem(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    /**
     * Products created before fulfillment types existed (or by a direct
     * Product::create call) fall back to their unit.
     */
    public function resolvedFulfillmentType(): string
    {
        if ($this->fulfillment_type && $this->fulfillment_type !== self::FULFILLMENT_STOCKED_ITEM) {
            return $this->fulfillment_type;
        }

        return match ($this->unit) {
            self::UNIT_GALLON => self::FULFILLMENT_REFILL,
            self::UNIT_PIECE => self::FULFILLMENT_NEW_CONTAINER,
            self::UNIT_MONTH => self::FULFILLMENT_DISPENSER_RENTAL,
            default => self::FULFILLMENT_STOCKED_ITEM,
        };
    }

    public function isRefill(): bool
    {
        return $this->resolvedFulfillmentType() === self::FULFILLMENT_REFILL;
    }

    public function isNewContainer(): bool
    {
        return $this->resolvedFulfillmentType() === self::FULFILLMENT_NEW_CONTAINER;
    }

    public function isDispenserRental(): bool
    {
        return $this->resolvedFulfillmentType() === self::FULFILLMENT_DISPENSER_RENTAL;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
