<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Delivery extends Model
{
    use HasFactory;

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_IN_TRANSIT = 'in_transit';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_RETURNED_TO_STATION = 'returned_to_station';

    // display order for a driver's run list
    public const STATUS_PRIORITY = [
        self::STATUS_ASSIGNED,
        self::STATUS_IN_TRANSIT,
        self::STATUS_DELIVERED,
        self::STATUS_RETURNED_TO_STATION,
    ];

    protected $fillable = [
        'order_id',
        'driver_id',
        'status',
        'delivered_at',
        'returned_containers',
    ];

    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
            'returned_containers' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function allowedTransitions(): array
    {
        return match ($this->status) {
            self::STATUS_ASSIGNED => [self::STATUS_IN_TRANSIT],
            self::STATUS_IN_TRANSIT => [self::STATUS_DELIVERED],
            self::STATUS_DELIVERED => [self::STATUS_RETURNED_TO_STATION],
            default => [],
        };
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }
}
