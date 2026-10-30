<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContainerTransaction extends Model
{
    use HasFactory;

    public const TYPE_SWAP = 'swap';

    public const TYPE_EXCHANGE_OUT = 'exchange_out';

    public const TYPE_RETURN = 'return';

    public const TYPE_PURCHASE = 'purchase';

    public const TYPE_LOSS = 'loss';

    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'customer_id',
        'order_id',
        'type',
        'quantity',
        'recorded_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
