<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'quantity',
        'threshold',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'threshold' => 'integer',
        ];
    }

    public function isLowStock(): bool
    {
        return $this->quantity <= $this->threshold;
    }
}
