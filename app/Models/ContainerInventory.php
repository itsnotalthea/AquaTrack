<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContainerInventory extends Model
{
    protected $table = 'container_inventory';

    public const AT_STATION_FULL = 'at_station_full';

    public const AT_STATION_EMPTY = 'at_station_empty';

    public const WITH_CUSTOMER = 'with_customer';

    public const OUT_FOR_DELIVERY = 'out_for_delivery';

    public const LOST_DAMAGED = 'lost_damaged';

    public const STATUSES = [
        self::AT_STATION_FULL,
        self::AT_STATION_EMPTY,
        self::WITH_CUSTOMER,
        self::OUT_FOR_DELIVERY,
        self::LOST_DAMAGED,
    ];

    protected $fillable = [
        'status',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function getFillRatioAttribute(): float
    {
        $atStation = (int) $this->{self::AT_STATION_FULL} + (int) $this->{self::AT_STATION_EMPTY};

        return $atStation > 0 ? (int) $this->{self::AT_STATION_FULL} / $atStation : 0.0;
    }
}
