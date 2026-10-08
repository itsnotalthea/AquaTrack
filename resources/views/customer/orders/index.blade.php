<x-app-layout>
    <x-slot name="title">My Orders | AquaTrack</x-slot>

    <div class="container section">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <h1 class="mb-0">My orders</h1>
            <a class="btn btn-aqua px-3" href="{{ route('customer.orders.create') }}">Place an order</a>
        </div>

        @if (session('status')) <div class="alert alert-success rounded-0">{{ session('status') }}</div> @endif

        @forelse ($orders as $order)
            <div class="px-card mb-3">
                <div class="row g-3 align-items-center">
                    <div class="col-md-3">
                        <div class="small text-muted">Order #{{ $order->id }}</div>
                        <div>{{ $order->created_at->format('M d, Y g:i A') }}</div>
                    </div>
                    <div class="col-md-3">
                        <div class="small text-muted">Items</div>
                        <div>
                            @foreach ($order->items as $item)
                                {{ $item->quantity }}&times; {{ $item->product->name }}@if (! $loop->last), @endif
                            @endforeach
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="small text-muted">Type</div>
                        <div class="text-capitalize">{{ $order->order_type }}</div>
                    </div>
                    <div class="col-md-2">
                        <div class="small text-muted">Status</div>
                        <div class="text-capitalize">{{ str_replace('_', ' ', $order->status) }}</div>
                    </div>
                    <div class="col-md-2 text-md-end">
                        <div class="small text-muted">Total</div>
                        <div>₱{{ number_format((float) $order->total_amount, 2) }}</div>
                        <a class="btn btn-sm btn-aqua px-2 mt-2" href="{{ route('customer.orders.show', $order) }}">View</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="px-card text-center">
                <p class="mb-2">You have not placed any orders yet.</p>
                <a class="btn btn-aqua px-3" href="{{ route('customer.orders.create') }}">Place your first order</a>
            </div>
        @endforelse

        @if ($orders->hasPages())
            <div class="mt-3">{{ $orders->links() }}</div>
        @endif
    </div>
</x-app-layout>