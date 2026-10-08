<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_PREPARING = 'preparing';

    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_READY_FOR_PICKUP = 'ready_for_pickup';

    public const STATUS_PICKED_UP = 'picked_up';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const TYPE_DELIVERY = 'delivery';

    public const TYPE_PICKUP = 'pickup';

    protected $fillable = [
        'customer_id',
        'order_type',
        'status',
        'total_amount',
        'payment_status',
        'payment_method',
        'delivery_address',
        'barangay',
        'preferred_date',
        'notes',
        'container_swap',
        'container_swap_qty',
        'weekly_reminder',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'preferred_date' => 'date',
            'container_swap' => 'boolean',
            'container_swap_qty' => 'integer',
            'weekly_reminder' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }

    public function containerTransactions(): HasMany
    {
        return $this->hasMany(ContainerTransaction::class);
    }

    public function isDelivery(): bool
    {
        return $this->order_type === self::TYPE_DELIVERY;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    // cancel is only allowed before preparation starts
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED], true);
    }

    public function allowedTransitions(): array
    {
        $flow = $this->isDelivery()
            ? [
                self::STATUS_PENDING => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED],
                self::STATUS_CONFIRMED => [self::STATUS_PREPARING, self::STATUS_CANCELLED],
                self::STATUS_PREPARING => [self::STATUS_OUT_FOR_DELIVERY],
                self::STATUS_OUT_FOR_DELIVERY => [self::STATUS_DELIVERED],
                self::STATUS_DELIVERED => [self::STATUS_COMPLETED],
            ]
            : [
                self::STATUS_PENDING => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED],
                self::STATUS_CONFIRMED => [self::STATUS_PREPARING, self::STATUS_CANCELLED],
                self::STATUS_PREPARING => [self::STATUS_READY_FOR_PICKUP],
                self::STATUS_READY_FOR_PICKUP => [self::STATUS_PICKED_UP],
                self::STATUS_PICKED_UP => [self::STATUS_COMPLETED],
            ];

        return $flow[$this->status] ?? [];
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }
}
