<x-app-layout>
    <x-slot name="title">Orders | AquaTrack</x-slot>

    <div class="container section">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <h1 class="mb-0">Orders</h1>
            <a class="btn btn-aqua px-3" href="{{ route('staff.reports.index', ['type' => 'orders']) }}">Reports</a>
        </div>

        @if (session('status')) <div class="alert alert-success rounded-0">{{ session('status') }}</div> @endif

        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-3">
                <input class="form-control" type="search" name="q" value="{{ request('q') }}" placeholder="Search customer">
            </div>
            <div class="col-md-2">
                <select class="form-select" name="status">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ str_replace('_', ' ', $s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="order_type">
                    <option value="">All types</option>
                    <option value="delivery" @selected(request('order_type') === 'delivery')>Delivery</option>
                    <option value="pickup" @selected(request('order_type') === 'pickup')>Pickup</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="payment_status">
                    <option value="">All payments</option>
                    <option value="unpaid" @selected(request('payment_status') === 'unpaid')>Unpaid</option>
                    <option value="paid" @selected(request('payment_status') === 'paid')>Paid</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-aqua w-100" type="submit">Filter</button>
            </div>
        </form>

        <div class="px-card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th><th>Date</th><th>Customer</th><th>Type</th><th>Barangay</th><th>Status</th><th>Payment</th><th class="text-end">Total</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                <td>{{ $order->id }}</td>
                                <td class="text-nowrap">{{ $order->created_at->format('M d, H:i') }}</td>
                                <td>{{ $order->customer?->name }}</td>
                                <td class="text-capitalize">{{ $order->order_type }}</td>
                                <td>{{ $order->barangay ?? '-' }}</td>
                                <td class="text-capitalize">{{ str_replace('_', ' ', $order->status) }}</td>
                                <td class="text-capitalize">{{ $order->payment_status }}</td>
                                <td class="text-end">₱{{ number_format((float) $order->total_amount, 2) }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-aqua px-2" href="{{ route('staff.orders.show', $order) }}">Open</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-muted">No orders match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $orders->links() }}</div>
        </div>
    </div>
</x-app-layout>