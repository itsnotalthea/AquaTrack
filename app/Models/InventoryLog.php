<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryLog extends Model
{
    protected $fillable = [
        'item_name',
        'change',
        'reason',
        'reference_id',
        'reference_type',
        'user_id',
        'from_status',
        'to_status',
        'quantity_before',
        'quantity_after',
    ];

    protected function casts(): array
    {
        return [
            'change' => 'integer',
            'quantity_before' => 'integer',
            'quantity_after' => 'integer',
        ];
    }
}
