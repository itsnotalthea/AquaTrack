<x-app-layout>
    <x-slot name="title">Order #{{ $order->id }} | AquaTrack</x-slot>

    <div class="container section">
        <a class="btn btn-light rounded-0 fw-bold px-3 mb-3" href="{{ route('customer.orders.index') }}">&larr; My orders</a>

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

                @if ($order->notes)
                    <div class="px-card">
                        <h2 class="h6">Notes</h2>
                        <p class="mb-0">{{ $order->notes }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>