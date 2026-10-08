<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContainerInventory;
use Illuminate\Http\JsonResponse;

class ContainerController extends Controller
{
    public function index(): JsonResponse
    {
        $containers = ContainerInventory::orderBy('id')->get();

        $full = (int) ($containers->firstWhere('status', ContainerInventory::AT_STATION_FULL)->quantity ?? 0);
        $empty = (int) ($containers->firstWhere('status', ContainerInventory::AT_STATION_EMPTY)->quantity ?? 0);
        $atStation = $full + $empty;

        return response()->json([
            'data' => $containers->map(fn (ContainerInventory $c) => [
                'status' => $c->status,
                'label' => str_replace('_', ' ', $c->status),
                'quantity' => (int) $c->quantity,
            ]),
            'fill_ratio' => $atStation > 0 ? round($full / $atStation, 4) : 0.0,
            'full' => $full,
            'empty' => $empty,
            'at_station' => $atStation,
        ]);
    }
}
