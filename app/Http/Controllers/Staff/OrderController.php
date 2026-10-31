<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\Payment;
use App\Models\User;
use App\Services\InsufficientStockException;
use App\Services\OrderFulfillment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = Order::query()
            ->with(['customer', 'items.product'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('order_type'), fn ($q) => $q->where('order_type', $request->string('order_type')))
            ->when($request->filled('payment_status'), fn ($q) => $q->where('payment_status', $request->string('payment_status')))
            ->when($request->filled('barangay'), fn ($q) => $q->where('barangay', $request->string('barangay')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($group) use ($term) {
                    $group->whereHas('customer', fn ($sub) => $sub->where(function ($name) use ($term) {
                        $name->where('first_name', 'like', $term)
                            ->orWhere('last_name', 'like', $term)
                            ->orWhere('email', 'like', $term)
                            ->orWhere('mobile', 'like', $term);
                    }));

                    if (ctype_digit((string) $request->input('q'))) {
                        $group->orWhere('id', (int) $request->input('q'));
                    }
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('staff.orders.index', [
            'orders' => $orders,
            'statuses' => $this->statuses(),
        ]);
    }

    public function show(Order $order): View
    {
        $order->load(['customer', 'items.product', 'delivery.driver', 'payment.recorder', 'dispenserRentals', 'statusLogs.user', 'changeLogs.user']);

        return view('staff.orders.show', [
            'order' => $order,
            'drivers' => User::where('role', User::ROLE_DRIVER)->orderBy('last_name')->get(),
        ]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in($order->allowedTransitions())],
            'failure_reason' => ['nullable', 'required_if:status,'.Order::STATUS_DELIVERY_FAILED, 'string', 'max:500'],
        ], [
            'status.in' => 'That status change is not allowed from '.$order->status.'.',
            'failure_reason.required_if' => 'Tell the customer why the delivery failed.',
        ]);

        if ($data['status'] === Order::STATUS_CANCELLED) {
            return $this->cancel($order, $request);
        }

        // a delivery order needs a driver before it can leave the station
        if ($data['status'] === Order::STATUS_OUT_FOR_DELIVERY && ! $order->delivery) {
            return back()->withErrors(['status' => 'Assign a driver before marking this order out for delivery.']);
        }

        $previous = $order->status;

        try {
            DB::transaction(function () use ($order, $data, $request, $previous) {
                if ($data['status'] === Order::STATUS_DELIVERY_FAILED) {
                    app(OrderFulfillment::class)->fail($order, $data['failure_reason'], $order->delivery);

                    return;
                }

                $order->update(['status' => $data['status']]);

                if ($data['status'] === Order::STATUS_OUT_FOR_DELIVERY) {
                    app(OrderFulfillment::class)->dispatch($order);
                }

                if ($order->isDelivery() && in_array($order->status, [Order::STATUS_OUT_FOR_DELIVERY, Order::STATUS_DELIVERED], true)) {
                    $order->delivery?->update([
                        'status' => $order->status === Order::STATUS_DELIVERED
                            ? Delivery::STATUS_DELIVERED
                            : Delivery::STATUS_IN_TRANSIT,
                        'delivered_at' => $order->status === Order::STATUS_DELIVERED ? now() : null,
                    ]);
                }

                if ($order->status === Order::STATUS_COMPLETED) {
                    app(OrderFulfillment::class)->handle($order);
                }

                $this->logStatus($order, $previous, $data['status'], $request->user()?->id);
            });
        } catch (InsufficientStockException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return $this->respond($request, 'Order updated to '.str_replace('_', ' ', $order->status).'.');
    }

    public function assignDriver(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->isDelivery(), 422, 'Pickup orders have no delivery.');

        $data = $request->validate([
            'driver_id' => ['required', Rule::exists('users', 'id')->where('role', User::ROLE_DRIVER)],
        ]);

        Delivery::updateOrCreate(
            ['order_id' => $order->id],
            ['driver_id' => $data['driver_id'], 'status' => Delivery::STATUS_ASSIGNED]
        );

        return back()->with('status', 'Driver assigned.');
    }

    // full amount only, recorded by staff once the order is delivered or picked up
    public function recordPayment(Request $request, Order $order): RedirectResponse|JsonResponse
    {
        if ($order->isPaid()) {
            return $this->fail($request, 'This order is already paid.');
        }

        if (! $this->canTakePayment($order)) {
            return $this->fail($request, 'Payment can only be recorded after the order is delivered or picked up.');
        }

        $data = $request->validate([
            'method' => ['required', Rule::in(['cash', 'gcash', 'maya'])],
        ]);

        DB::transaction(function () use ($order, $data, $request) {
            Payment::create([
                'order_id' => $order->id,
                'amount' => $order->total_amount,
                'method' => $data['method'],
                'recorded_by' => $request->user()->id,
            ]);

            $order->update([
                'payment_status' => 'paid',
                'payment_method' => $data['method'],
            ]);
        });

        // a paid pickup order is finished, delivery orders are closed by the driver
        if (! $order->isDelivery() && $order->status === Order::STATUS_PICKED_UP) {
            $previous = $order->status;
            $order->update(['status' => Order::STATUS_COMPLETED]);

            try {
                app(OrderFulfillment::class)->handle($order->fresh());
            } catch (InsufficientStockException $e) {
                return $this->fail($request, $e->getMessage());
            }

            $this->logStatus($order, $previous, Order::STATUS_COMPLETED, $request->user()?->id);
        }

        return $this->respond($request, 'Payment recorded.');
    }

    private function fail(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return back()->withErrors(['payment' => $message]);
    }

    private function cancel(Order $order, ?Request $request = null): RedirectResponse|JsonResponse
    {
        if (! $order->canBeCancelled()) {
            if ($request?->expectsJson()) {
                return response()->json(['message' => 'This order can no longer be cancelled.'], 422);
            }

            return back()->withErrors(['status' => 'This order can no longer be cancelled.']);
        }

        $previous = $order->status;
        $order->update(['status' => Order::STATUS_CANCELLED]);
        $this->logStatus($order, $previous, Order::STATUS_CANCELLED, $request?->user()?->id);

        if ($request?->expectsJson()) {
            return response()->json(['message' => 'Order cancelled.']);
        }

        return back()->with('status', 'Order cancelled.');
    }

    // payment is taken after delivery or pickup, or after completion
    private function canTakePayment(Order $order): bool
    {
        $threshold = $order->isDelivery() ? Order::STATUS_DELIVERED : Order::STATUS_PICKED_UP;

        return in_array($order->status, [$threshold, Order::STATUS_COMPLETED], true);
    }

    private function logStatus(Order $order, ?string $from, string $to, ?int $userId): void
    {
        OrderStatusLog::create([
            'order_id' => $order->id,
            'from_status' => $from,
            'to_status' => $to,
            'user_id' => $userId,
        ]);
    }

    private function respond(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('status', $message);
    }

    private function statuses(): array
    {
        return [
            Order::STATUS_PENDING,
            Order::STATUS_CONFIRMED,
            Order::STATUS_PREPARING,
            Order::STATUS_OUT_FOR_DELIVERY,
            Order::STATUS_DELIVERED,
            Order::STATUS_READY_FOR_PICKUP,
            Order::STATUS_PICKED_UP,
            Order::STATUS_COMPLETED,
            Order::STATUS_DELIVERY_FAILED,
            Order::STATUS_CANCELLED,
        ];
    }
}
