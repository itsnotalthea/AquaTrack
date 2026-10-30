<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\ContainerInventory;
use App\Models\Delivery;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $driverId = $request->user()->id;

        $mine = Delivery::where('driver_id', $driverId);
        $monthStart = now()->startOfMonth();
        $calendarStart = $monthStart->copy()->startOfWeek();

        $containers = ContainerInventory::orderBy('id')->get()->keyBy('status');
        $full = (int) ($containers[ContainerInventory::AT_STATION_FULL]->quantity ?? 0);
        $empty = (int) ($containers[ContainerInventory::AT_STATION_EMPTY]->quantity ?? 0);

        $calendarDays = collect(range(0, 41))->map(function (int $offset) use ($calendarStart, $mine): array {
            $date = $calendarStart->copy()->addDays($offset);

            return [
                'date' => $date,
                'day' => $date->day,
                'thisMonth' => $date->month === now()->month,
                'isToday' => $date->isToday(),
                'count' => (clone $mine)->whereDate('created_at', $date->toDateString())->count(),
            ];
        })->chunk(7);

        return view('driver.dashboard', [
            'active' => (clone $mine)->whereIn('status', [
                Delivery::STATUS_ASSIGNED,
                Delivery::STATUS_IN_TRANSIT,
            ])->count(),
            'today' => (clone $mine)->whereDate('created_at', today())->count(),
            'completed' => (clone $mine)->where('status', Delivery::STATUS_RETURNED_TO_STATION)->count(),
            'deliveries' => (clone $mine)
                ->with('order.customer')
                ->orderByRaw($this->statusOrder())
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(),
            'waitingPayment' => Order::whereIn('status', [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])
                ->where('payment_status', 'unpaid')
                ->whereHas('delivery', fn ($q) => $q->where('driver_id', $driverId))
                ->count(),
            'fillRatio' => ($full + $empty) > 0 ? $full / ($full + $empty) : 0.0,
            'calendarDays' => $calendarDays,
            'monthLabel' => $monthStart->translatedFormat('F Y'),
        ]);
    }

    // FIELD() is mysql only, CASE keeps this portable for the sqlite test suite
    private function statusOrder(): string
    {
        $cases = collect(Delivery::STATUS_PRIORITY)
            ->map(fn (string $status, int $i) => "WHEN '{$status}' THEN {$i}")
            ->implode(' ');

        return "CASE deliveries.status {$cases} ELSE 9 END";
    }
}
