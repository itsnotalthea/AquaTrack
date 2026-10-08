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
    ];

    protected function casts(): array
    {
        return [
            'change' => 'integer',
        ];
    }
}
