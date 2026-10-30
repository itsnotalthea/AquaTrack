<x-app-layout>
    <x-slot name="title">Orders | AquaTrack</x-slot>

    <div class="container section ops-orders">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
            <div>
                <h1 class="mb-1">Orders</h1>
                <p class="text-muted mb-0">Search, filter, and move orders through fulfillment.</p>
            </div>
            <a class="btn btn-aqua px-3" href="{{ route('operations.reports.index', ['type' => 'orders']) }}">
                <x-icon name="chart-bar-big" /> Reports
            </a>
        </div>

        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-4">
                <div class="search-wrap">
                    <x-icon name="search" />
                    <input class="form-control" type="search" name="q" value="{{ request('q') }}" placeholder="Search name, email, mobile, or order #">
                </div>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="status" aria-label="Status">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ str_replace('_', ' ', $s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="order_type" aria-label="Order type">
                    <option value="">All types</option>
                    <option value="delivery" @selected(request('order_type') === 'delivery')>Delivery</option>
                    <option value="pickup" @selected(request('order_type') === 'pickup')>Pickup</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="payment_status" aria-label="Payment status">
                    <option value="">All payments</option>
                    <option value="unpaid" @selected(request('payment_status') === 'unpaid')>Unpaid</option>
                    <option value="paid" @selected(request('payment_status') === 'paid')>Paid</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-aqua flex-fill" type="submit">Filter</button>
                <a class="btn btn-light" href="{{ route('operations.orders.index') }}" aria-label="Reset filters"><x-icon name="reload" /></a>
            </div>
        </form>

        <div class="px-card">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
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
                                <td><span class="badge badge-soft text-capitalize">{{ str_replace('_', ' ', $order->status) }}</span></td>
                                <td class="text-capitalize">{{ $order->payment_status }}</td>
                                <td class="text-end">₱{{ number_format((float) $order->total_amount, 2) }}</td>
                                <td class="text-end text-nowrap">
                                    <button type="button" class="btn btn-sm btn-light px-2"
                                            data-quick-order
                                            data-order-id="{{ $order->id }}"
                                            data-order-customer="{{ $order->customer?->name }}"
                                            data-order-status="{{ str_replace('_', ' ', $order->status) }}"
                                            data-order-total="₱{{ number_format((float) $order->total_amount, 2) }}"
                                            data-order-items="{{ $order->items->map(fn ($i) => $i->quantity.'× '.$i->resolvedName())->implode(', ') }}">
                                        <x-icon name="eye" /> Quick view
                                    </button>
                                    <a class="btn btn-sm btn-aqua px-2" href="{{ route('operations.orders.show', $order) }}">Open</a>
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

    @push('modals')
        <div class="modal fade" id="quickOrderModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title h5">Order #<span data-quick-id></span></h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-1"><strong data-quick-customer></strong></p>
                        <p class="text-muted small mb-3" data-quick-items></p>
                        <div class="d-flex justify-content-between border-top pt-2">
                            <span class="text-capitalize" data-quick-status></span>
                            <strong data-quick-total></strong>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endpush

    @push('scripts')
        <script>
            (function () {
                document.addEventListener('click', function (event) {
                    var trigger = event.target.closest('[data-quick-order]');

                    if (!trigger) {
                        return;
                    }

                    document.querySelector('[data-quick-id]').textContent = trigger.dataset.orderId;
                    document.querySelector('[data-quick-customer]').textContent = trigger.dataset.orderCustomer || 'Customer';
                    document.querySelector('[data-quick-items]').textContent = trigger.dataset.orderItems || 'No items';
                    document.querySelector('[data-quick-status]').textContent = trigger.dataset.orderStatus || '';
                    document.querySelector('[data-quick-total]').textContent = trigger.dataset.orderTotal || '';
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('quickOrderModal')).show();
                });
            })();
        </script>
    @endpush
</x-app-layout>
