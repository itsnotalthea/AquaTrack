<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.index', [
            'deliveryFee' => Setting::get(Setting::DELIVERY_FEE, 30),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'delivery_fee' => ['required', 'numeric', 'min:0', 'max:999999.99'],
        ]);

        Setting::put(Setting::DELIVERY_FEE, $validated['delivery_fee']);

        return back()->with('status', 'Settings saved.');
    }
}
