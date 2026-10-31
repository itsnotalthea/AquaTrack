<x-app-layout>
    <x-slot name="title">Order #{{ $order->id }} | AquaTrack</x-slot>

    <div class="container section">
        <div class="d-flex flex-wrap gap-2 mb-3">
            <a class="btn btn-light rounded-0 fw-bold px-3" href="{{ route('customer.orders.index') }}">&larr; My orders</a>
            @if ($order->status === \App\Models\Order::STATUS_PENDING)
                <a class="btn btn-aqua px-3" href="{{ route('customer.orders.edit', $order) }}">Edit order</a>
            @endif
        </div>

        @if (session('status')) <div class="alert alert-success rounded-0">{{ session('status') }}</div> @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="px-card">
                    <h1 class="h5">Order #{{ $order->id }}</h1>
                    <p class="text-muted small mb-3">Placed {{ $order->created_at->format('M d, Y g:i A') }}</p>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->items as $item)
                                    <tr>
                                        <td>
                                            {{ $item->resolvedName() }}
                                            @if ($item->resolvedContainerMode())
                                                <div class="small text-muted text-capitalize">{{ str_replace('_', ' ', $item->resolvedContainerMode()) }}</div>
                                            @endif
                                        </td>
                                        <td class="text-end">{{ $item->quantity }}</td>
                                        <td class="text-end">₱{{ number_format((float) $item->price, 2) }}</td>
                                        <td class="text-end">₱{{ number_format((float) $item->subtotal, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between mt-3 small">
                        <span class="text-muted">Delivery fee</span>
                        <span>₱{{ number_format((float) $order->delivery_fee, 2) }}</span>
                    </div>
                    <div class="total-box mt-2 text-end">Total: ₱{{ number_format((float) $order->total_amount, 2) }}</div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="px-card mb-3">
                    <h2 class="h6">Status</h2>
                    <p class="text-capitalize mb-0">{{ str_replace('_', ' ', $order->status) }}</p>
                    <hr>
                    <h2 class="h6">Payment</h2>
                    <p class="mb-0">
                        <span class="text-capitalize">{{ $order->payment_status }}</span>
                        @if ($order->payment_method)
                            &middot; <span class="text-capitalize">{{ $order->payment_method }}</span>
                        @endif
                    </p>

                    @if ($order->payment_reference)
                        <div class="mt-2 small">
                            <div class="text-muted">Reference</div>
                            <div>{{ $order->payment_reference }}</div>
                        </div>
                    @endif

                    @if ($order->payment_proof_path)
                        <div class="mt-2">
                            <div class="text-muted small">Proof</div>
                            <a href="{{ Storage::disk('public')->url($order->payment_proof_path) }}" target="_blank" rel="noopener">
                                <img src="{{ Storage::disk('public')->url($order->payment_proof_path) }}" alt="Payment proof" class="img-fluid rounded border mt-1" style="max-height: 180px;">
                            </a>
                        </div>
                    @endif
                </div>

                <div class="px-card mb-3">
                    <h2 class="h6">Order type</h2>
                    <p class="text-capitalize mb-2">{{ $order->order_type }}</p>
                    @if ($order->delivery_address)
                        <h2 class="h6">Delivery address</h2>
                        <p class="mb-0">{{ $order->delivery_address }}</p>
                    @endif
                    @if ($order->preferred_date)
                        <h2 class="h6 mt-2">Preferred date</h2>
                        <p class="mb-0">{{ $order->preferred_date->format('M d, Y') }}</p>
                    @endif
                </div>

                @if ($order->container_swap)
                    <div class="px-card mb-3">
                        <h2 class="h6">Container swap</h2>
                        <p class="mb-0">Swapping {{ $order->container_swap_qty }} container{{ $order->container_swap_qty == 1 ? '' : 's' }}.</p>
                    </div>
                @endif

                @if ($order->dispenserRentals->isNotEmpty())
                    <div class="px-card mb-3">
                        <h2 class="h6">Dispenser rentals</h2>
                        @foreach ($order->dispenserRentals as $rental)
                            <p class="mb-1 small">
                                {{ $rental->quantity }} unit(s) · <span class="text-capitalize">{{ $rental->status }}</span><br>
                                <span class="text-muted">Due {{ $rental->due_at?->format('M d, Y') ?? '—' }}</span>
                            </p>
                        @endforeach
                    </div>
                @endif

                <div class="px-card">
                    <h2 class="h6">Notes</h2>
                    <p class="mb-0">{{ $order->notes ?: 'No notes added.' }}</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>