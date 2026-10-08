<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\JsonResponse;

class InventoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->items()->map(fn (InventoryItem $i) => [
                'name' => $i->name,
                'label' => str_replace('_', ' ', $i->name),
                'quantity' => (int) $i->quantity,
                'threshold' => (int) $i->threshold,
                'low' => $i->isLowStock(),
            ]),
        ]);
    }

    public function lowStock(): JsonResponse
    {
        $low = $this->items()->filter(fn (InventoryItem $i) => $i->isLowStock())->values();

        return response()->json([
            'count' => $low->count(),
            'data' => $low->map(fn (InventoryItem $i) => [
                'name' => $i->name,
                'label' => str_replace('_', ' ', $i->name),
                'quantity' => (int) $i->quantity,
                'threshold' => (int) $i->threshold,
            ]),
        ]);
    }

    private function items()
    {
        return InventoryItem::orderBy('name')->get();
    }
}
