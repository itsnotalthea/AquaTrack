<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(): View
    {
        return view('admin.inventory.index', [
            'items' => InventoryItem::orderBy('name')->get(),
        ]);
    }

    // only thresholds are editable here
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'thresholds' => ['required', 'array'],
            'thresholds.*' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($validated['thresholds'] as $id => $threshold) {
            InventoryItem::whereKey($id)->update(['threshold' => $threshold]);
        }

        return back()->with('status', 'Thresholds updated.');
    }
}
