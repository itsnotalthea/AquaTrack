<x-app-layout>
    <x-slot name="title">Delivery #{{ $delivery->order_id }} | AquaTrack</x-slot>

    <div class="container section">
        <a class="btn btn-light rounded-0 fw-bold px-3 mb-3" href="{{ route('driver.deliveries.index') }}">&larr; My deliveries</a>

        @if (session('status')) <div class="alert alert-success rounded-0">{{ session('status') }}</div> @endif
        @if ($errors->any()) <div class="alert alert-danger rounded-0">{{ $errors->first() }}</div> @endif

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="px-card mb-4">
                    <h1 class="h5">Delivery for order #{{ $delivery->order_id }}</h1>
                    <p class="small text-muted mb-3 text-capitalize">
                        Status: {{ str_replace('_', ' ', $delivery->status) }}
                    </p>

                    <h2 class="h6">Deliver to</h2>
                    <p class="mb-1"><strong>{{ $delivery->order?->customer?->name }}</strong></p>
                    <p class="mb-1">{{ $delivery->order?->delivery_address ?? 'No address on file' }}</p>
                    @if ($delivery->order?->barangay)
                        <p class="small text-muted mb-1">Barangay: {{ $delivery->order->barangay }}</p>
                    @endif
                    @if ($delivery->order?->customer?->mobile)
                        <p class="mb-0">Mobile: {{ $delivery->order->customer->mobile }}</p>
                    @endif
                </div>

                <div class="px-card">
                    <h2 class="h6">Items to carry</h2>
                    <ul class="mb-0">
                        @foreach ($delivery->order?->items ?? [] as $item)
                            <li>{{ $item->quantity }} &times; {{ $item->product->name }}</li>
                        @endforeach
                    </ul>
                    @if ($delivery->order?->notes)
                        <p class="small text-muted mt-2 mb-0">Customer note: {{ $delivery->order->notes }}</p>
                    @endif
                </div>
            </div>

            <div class="col-lg-5">
                <div class="px-card mb-3">
                    <h2 class="h6">Update status</h2>
                    @php $next = $delivery->allowedTransitions(); @endphp

                    @if (empty($next))
                        <p class="mb-0 text-muted">This run is closed. Nothing further to do.</p>
                    @else
                        <form method="POST" action="{{ route('driver.deliveries.status', $delivery) }}">
                            @csrf
                            @method('PUT')
                            <label class="form-label" for="status">Move to</label>
                            <select class="form-select mb-2" id="status" name="status" required>
                                @foreach ($next as $status)
                                    <option value="{{ $status }}">{{ str_replace('_', ' ', $status) }}</option>
                                @endforeach
                            </select>
                            <button class="btn btn-aqua w-100" type="submit">Update</button>
                        </form>
                    @endif
                </div>

                <div class="px-card">
                    <h2 class="h6">Container return</h2>
                    <p class="small text-muted">
                        Empty containers the customer handed back at the door.
                        @if ($delivery->order?->container_swap)
                            They ordered a swap of {{ $delivery->order->container_swap_qty }}.
                        @endif
                    </p>

                    <form method="POST" action="{{ route('driver.deliveries.return', $delivery) }}">
                        @csrf
                        @method('PUT')
                        <label class="form-label" for="returned_containers">Containers returned</label>
                        <input class="form-control mb-2" type="number" min="0" max="50" id="returned_containers"
                               name="returned_containers" value="{{ old('returned_containers', $delivery->returned_containers ?? 0) }}" required>
                        @error('returned_containers') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        <button class="btn btn-aqua w-100" type="submit">Save return</button>
                    </form>

                    <p class="small text-muted mt-2 mb-0">
                        Recording a return while the run is delivered also closes the run and completes the order.
                        Payment is recorded by staff, not by you.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>