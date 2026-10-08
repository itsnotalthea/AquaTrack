<?php

namespace App\Http\Controllers;

use App\Models\ContainerInventory;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    // sends each role to its own landing page
    public function index(): RedirectResponse
    {
        return redirect()->route($this->homeForRole(auth()->user()->role));
    }

    public function admin(): View
    {
        $containers = ContainerInventory::orderBy('id')->get()->keyBy('status');

        return view('admin.dashboard', [
            'containers' => $containers,
            'pendingOrders' => Order::whereIn('status', [
                Order::STATUS_PENDING,
                Order::STATUS_CONFIRMED,
                Order::STATUS_PREPARING,
            ])->count(),
            'lowStock' => InventoryItem::all()
                ->filter(fn (InventoryItem $item) => $item->isLowStock())
                ->values(),
            'salesToday' => (float) Order::whereDate('created_at', today())
                ->whereNot('status', Order::STATUS_CANCELLED)
                ->sum('total_amount'),
            'orderCountToday' => Order::whereDate('created_at', today())->count(),
        ]);
    }

    public function customer(): View
    {
        return view('customer.dashboard');
    }

    private function homeForRole(string $role): string
    {
        return match ($role) {
            User::ROLE_ADMIN => 'admin.dashboard',
            User::ROLE_STAFF => 'staff.dashboard',
            User::ROLE_DRIVER => 'driver.dashboard',
            default => 'customer.dashboard',
        };
    }
}
