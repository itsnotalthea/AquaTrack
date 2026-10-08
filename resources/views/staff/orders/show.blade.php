<x-app-layout>
    <x-slot name="title">Order #{{ $order->id }} | AquaTrack</x-slot>

    <div class="container section">
        <a class="btn btn-light rounded-0 fw-bold px-3 mb-3" href="{{ route('staff.orders.index') }}">&larr; Orders</a>

        @if (session('status')) <div class="alert alert-success rounded-0">{{ session('status') }}</div> @endif
        @if ($errors->any()) <div class="alert alert-danger rounded-0">{{ $errors->first() }}</div> @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="px-card mb-4">
                    <h1 class="h5">Order #{{ $order->id }}</h1>
                    <p class="small text-muted mb-3">{{ $order->created_at->format('M d, Y g:i A') }}</p>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead><tr><th>Product</th><th class="text-end">Qty</th><th class="text-end">Price</th><th class="text-end">Subtotal</th></tr></thead>
                            <tbody>
                                @foreach ($order->items as $item)
                                    <tr>
                                        <td>{{ $item->product->name }}</td>
                                        <td class="text-end">{{ $item->quantity }}</td>
                                        <td class="text-end">₱{{ number_format((float) $item->price, 2) }}</td>
                                        <td class="text-end">₱{{ number_format((float) $item->subtotal, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="total-box mt-3 text-end">Total: ₱{{ number_format((float) $order->total_amount, 2) }}</div>
                </div>

                <div class="px-card">
                    <h2 class="h6">Update status</h2>
                    @php $next = $order->allowedTransitions(); @endphp

                    @if (empty($next))
                        <p class="mb-0 text-muted">This order is {{ str_replace('_', ' ', $order->status) }} and has no further transitions.</p>
                    @else
                        <form method="POST" action="{{ route('staff.orders.status', $order) }}" data-ajax-post class="row g-2 align-items-end">
                            @csrf
                            @method('PUT')
                            <div class="col-md-8">
                                <label class="form-label" for="status">Move to</label>
                                <select class="form-select" id="status" name="status" required>
                                    @foreach ($next as $status)
                                        <option value="{{ $status }}">{{ str_replace('_', ' ', $status) }}</option>
                                    @endforeach
                                </select>
                                @error('status') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-aqua w-100" type="submit">Update</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>

            <div class="col-lg-4">
                <div class="px-card mb-3">
                    <h2 class="h6">Customer</h2>
                    <p class="mb-1">{{ $order->customer?->name }}</p>
                    <p class="small text-muted mb-0">{{ $order->customer?->email }}<br>{{ $order->customer?->mobile }}</p>
                </div>

                <div class="px-card mb-3">
                    <h2 class="h6">Order</h2>
                    <p class="mb-0 text-capitalize">{{ $order->order_type }}</p>
                    @if ($order->delivery_address)
                        <p class="small mt-2 mb-1">{{ $order->delivery_address }}</p>
                    @endif
                    @if ($order->barangay)
                        <p class="small text-muted mb-0">Barangay: {{ $order->barangay }}</p>
                    @endif
                    @if ($order->preferred_date)
                        <p class="small text-muted mb-0">Preferred: {{ $order->preferred_date->format('M d, Y') }}</p>
                    @endif
                    @if ($order->container_swap)
                        <p class="small text-muted mb-0">Swap: {{ $order->container_swap_qty }} container(s)</p>
                    @endif
                    @if ($order->notes)
                        <p class="small text-muted mb-0">Notes: {{ $order->notes }}</p>
                    @endif
                </div>

                @if ($order->isDelivery())
                    <div class="px-card mb-3">
                        <h2 class="h6">Driver</h2>
                        <form method="POST" action="{{ route('staff.orders.driver', $order) }}">
                            @csrf
                            @method('PUT')
                            <label class="form-label" for="driver_id">Assign driver</label>
                            <select class="form-select mb-2" id="driver_id" name="driver_id" required>
                                @foreach ($drivers as $driver)
                                    <option value="{{ $driver->id }}" @selected($order->delivery?->driver_id === $driver->id)>{{ $driver->name }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn-aqua w-100" type="submit">{{ $order->delivery ? 'Reassign' : 'Assign' }}</button>
                        </form>
                        @if ($order->delivery)
                            <p class="small text-muted mt-2 mb-0">
                                Delivery status: <span class="text-capitalize">{{ str_replace('_', ' ', $order->delivery->status) }}</span>
                            </p>
                        @endif
                    </div>
                @endif

                <div class="px-card mb-3">
                    <h2 class="h6">Payment</h2>
                    @if ($order->isPaid())
                        <p class="mb-0">
                            Paid via <span class="text-capitalize">{{ $order->payment_method }}</span>
                            @if ($order->payment)
                                <br><span class="small text-muted">by {{ $order->payment->recorder?->name }}</span>
                            @endif
                        </p>
                    @else
                        <p class="small text-muted">
                            Records the full order total of ₱{{ number_format((float) $order->total_amount, 2) }}.
                            No partial payments.
                        </p>
                        <button type="button" class="btn btn-aqua w-100" data-bs-toggle="modal" data-bs-target="#paymentModal"
                                data-payment-order="{{ $order->id }}"
                                data-payment-label="{{ $order->customer?->name }} — ₱{{ number_format((float) $order->total_amount, 2) }}">
                            Record payment
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if (! $order->isPaid())
        <div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" data-payment-form>
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h2 class="modal-title h5">Record payment</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-2" data-payment-label></p>
                            <label class="form-label" for="paymentMethod">Method</label>
                            <select class="form-select" id="paymentMethod" name="method" required>
                                <option value="cash">Cash</option>
                                <option value="gcash">GCash</option>
                                <option value="maya">Maya</option>
                            </select>
                            <p class="small text-muted mt-2 mb-0">
                                Payments are full amount only. Drivers never record payments.
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light rounded-0 fw-bold" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-aqua" type="submit">Save payment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</x-app-layout>