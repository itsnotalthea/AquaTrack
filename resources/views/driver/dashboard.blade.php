<x-app-layout>
    <x-slot name="title">Driver Dashboard | AquaTrack</x-slot>

    <div class="container section">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <h1 class="mb-0">My Deliveries</h1>
            <a class="btn btn-aqua px-3" href="{{ route('driver.deliveries.index') }}">All deliveries</a>
        </div>

        @if (session('status')) <div class="alert alert-success rounded-0">{{ session('status') }}</div> @endif

        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="px-card">
                    <div class="ico">🚚</div>
                    <h4 class="h6 mt-2">Active runs</h4>
                    <p class="display-6 mb-0">{{ $active }}</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="px-card">
                    <div class="ico">🗓️</div>
                    <h4 class="h6 mt-2">Assigned today</h4>
                    <p class="display-6 mb-0">{{ $today }}</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="px-card">
                    <div class="ico">✅</div>
                    <h4 class="h6 mt-2">Completed runs</h4>
                    <p class="display-6 mb-0">{{ $completed }}</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="px-card">
                    <div class="ico">💵</div>
                    <h4 class="h6 mt-2">With staff for payment</h4>
                    <p class="display-6 mb-0">{{ $waitingPayment }}</p>
                </div>
            </div>
        </div>

        <div class="px-card">
            <h2 class="h5 mb-3">Next up</h2>

            @forelse ($deliveries as $delivery)
                <div class="border-bottom py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <div><strong>Order #{{ $delivery->order_id }}</strong> &middot; {{ $delivery->order?->customer?->name }}</div>
                        <div class="small text-muted">
                            {{ $delivery->order?->barangay ?? 'No barangay' }}
                            @if ($delivery->order?->delivery_address) &middot; {{ $delivery->order->delivery_address }} @endif
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="small text-capitalize">{{ str_replace('_', ' ', $delivery->status) }}</div>
                        <a class="btn btn-sm btn-aqua px-2 mt-1" href="{{ route('driver.deliveries.show', $delivery) }}">Open</a>
                    </div>
                </div>
            @empty
                <p class="text-muted mb-0">No deliveries are assigned to you yet.</p>
            @endforelse
        </div>
    </div>
</x-app-layout>