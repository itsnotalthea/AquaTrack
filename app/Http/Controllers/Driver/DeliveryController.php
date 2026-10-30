<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\ContainerTransaction;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Services\InsufficientStockException;
use App\Services\OrderFulfillment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function index(Request $request): View
    {
        $deliveries = $this->assignedDeliveries($request)
            ->with('order.customer', 'order.items.product')
            ->orderByRaw($this->statusOrder())
            ->orderByDesc('deliveries.created_at')
            ->paginate(15);

        return view('driver.deliveries.index', ['deliveries' => $deliveries]);
    }

    // FIELD() is mysql only, CASE keeps this portable for the sqlite test suite
    public function statusOrder(): string
    {
        $cases = collect(Delivery::STATUS_PRIORITY)
            ->map(fn (string $status, int $i) => "WHEN '{$status}' THEN {$i}")
            ->implode(' ');

        return "CASE deliveries.status {$cases} ELSE 9 END";
    }

    public function show(Delivery $delivery): View|RedirectResponse
    {
        $this->authorizeDelivery($delivery);

        $delivery->load('order.customer', 'order.items.product');

        return view('driver.deliveries.show', ['delivery' => $delivery]);
    }

    // delivering also advances the order, returning completes it
    public function updateStatus(Request $request, Delivery $delivery): RedirectResponse
    {
        $this->authorizeDelivery($delivery);

        $data = $request->validate([
            'status' => ['required', Rule::in($delivery->allowedTransitions())],
            'failure_reason' => ['nullable', 'required_if:status,'.Delivery::STATUS_FAILED, 'string', 'max:500'],
        ], [
            'status.in' => 'That status change is not allowed from '.$delivery->status.'.',
            'failure_reason.required_if' => 'Add a short reason so the customer can be informed.',
        ]);

        $next = $data['status'];
        $previousStatus = $delivery->status;
        $order = $delivery->order;

        if ($next === Delivery::STATUS_FAILED) {
            app(OrderFulfillment::class)->fail($order, $data['failure_reason'], $delivery);

            return back()->with('status', 'Delivery marked failed. Stock has been returned to the station.');
        }

        try {
            DB::transaction(function () use ($delivery, $order, $next, $previousStatus) {
                $delivery->update([
                    'status' => $next,
                    'delivered_at' => $next === Delivery::STATUS_DELIVERED ? now() : $delivery->delivered_at,
                ]);

                if ($next === Delivery::STATUS_DELIVERED && $order->status === Order::STATUS_OUT_FOR_DELIVERY) {
                    $previous = $order->status;
                    $order->update(['status' => Order::STATUS_DELIVERED]);
                    OrderStatusLog::create([
                        'order_id' => $order->id,
                        'from_status' => $previous,
                        'to_status' => Order::STATUS_DELIVERED,
                        'user_id' => request()->user()?->id,
                    ]);
                }

                // A failed run has already been reversed; closing the run must not
                // complete or move stock a second time.
                if ($next === Delivery::STATUS_RETURNED_TO_STATION && $previousStatus !== Delivery::STATUS_FAILED) {
                    $previous = $order->status;
                    $order->update(['status' => Order::STATUS_COMPLETED]);
                    app(OrderFulfillment::class)->handle($order->fresh());
                    OrderStatusLog::create([
                        'order_id' => $order->id,
                        'from_status' => $previous,
                        'to_status' => Order::STATUS_COMPLETED,
                        'user_id' => request()->user()?->id,
                    ]);
                }
            });
        } catch (InsufficientStockException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Delivery marked '.str_replace('_', ' ', $next).'.');
    }

    // what the customer actually handed back at the door
    public function recordReturn(Request $request, Delivery $delivery): RedirectResponse
    {
        $this->authorizeDelivery($delivery);

        $data = $request->validate([
            'returned_containers' => ['required', 'integer', 'min:0', 'max:50'],
        ], [
            'returned_containers.required' => 'Enter how many empty containers came back (use 0 if none).',
        ]);

        $delivery->update(['returned_containers' => (int) $data['returned_containers']]);
        $delivery->order->update(['returned_containers' => (int) $data['returned_containers']]);

        if ($delivery->status === Delivery::STATUS_DELIVERED) {
            try {
                DB::transaction(function () use ($delivery, $data) {
                    ContainerTransaction::create([
                        'customer_id' => $delivery->order->customer_id,
                        'order_id' => $delivery->order_id,
                        'type' => ContainerTransaction::TYPE_RETURN,
                        'quantity' => (int) $data['returned_containers'],
                    ]);

                    $delivery->update(['status' => Delivery::STATUS_RETURNED_TO_STATION]);

                    $previous = $delivery->order->status;
                    $delivery->order->update(['status' => Order::STATUS_COMPLETED]);
                    app(OrderFulfillment::class)->handle($delivery->order->fresh());
                    OrderStatusLog::create([
                        'order_id' => $delivery->order_id,
                        'from_status' => $previous,
                        'to_status' => Order::STATUS_COMPLETED,
                        'user_id' => request()->user()?->id,
                    ]);
                });
            } catch (InsufficientStockException $e) {
                return back()->withErrors(['returned_containers' => $e->getMessage()]);
            }

            return back()->with('status', 'Return recorded and run closed.');
        }

        return back()->with('status', 'Container return saved.');
    }

    // drivers only ever see their own runs
    private function assignedDeliveries(Request $request)
    {
        return Delivery::where('driver_id', $request->user()->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));
    }

    private function authorizeDelivery(Delivery $delivery): void
    {
        abort_if($delivery->driver_id !== request()->user()->id, 403, 'That delivery is not assigned to you.');
    }
}
