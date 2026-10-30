<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispenserRental extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_RETURNED = 'returned';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_LOST = 'lost';

    public const STATUS_DAMAGED = 'damaged';

    public const CONDITION_GOOD = 'good';

    public const CONDITION_DAMAGED = 'damaged';

    public const CONDITION_LOST = 'lost';

    protected $fillable = [
        'order_id',
        'order_item_id',
        'customer_id',
        'product_id',
        'quantity',
        'returned_quantity',
        'started_at',
        'due_at',
        'returned_at',
        'status',
        'condition',
        'notes',
        'handled_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'returned_quantity' => 'integer',
            'started_at' => 'datetime',
            'due_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->due_at->isPast();
    }

    public function getRemainingAttribute(): int
    {
        return max(0, $this->quantity - $this->returned_quantity);
    }
}
