<x-app-layout>
    <x-slot name="title">Deliveries | AquaTrack</x-slot>

    <div class="container section">
        <h1 class="mb-4">My Deliveries</h1>

        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-4">
                <select class="form-select" name="status">
                    <option value="">All statuses</option>
                    <option value="assigned" @selected(request('status') === 'assigned')>Assigned</option>
                    <option value="in_transit" @selected(request('status') === 'in_transit')>In transit</option>
                    <option value="delivered" @selected(request('status') === 'delivered')>Delivered</option>
                    <option value="returned_to_station" @selected(request('status') === 'returned_to_station')>Returned to station</option>
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
                        <tr><th>Order</th><th>Customer</th><th>Barangay</th><th>Address</th><th>Status</th><th>Returned</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse ($deliveries as $delivery)
                            <tr>
                                <td>#{{ $delivery->order_id }}</td>
                                <td>{{ $delivery->order?->customer?->name }}</td>
                                <td>{{ $delivery->order?->barangay ?? '-' }}</td>
                                <td class="small">{{ $delivery->order?->delivery_address ?? '-' }}</td>
                                <td class="text-capitalize">{{ str_replace('_', ' ', $delivery->status) }}</td>
                                <td>{{ $delivery->returned_containers ?? '-' }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-aqua px-2" href="{{ route('driver.deliveries.show', $delivery) }}">Open</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-muted">Nothing assigned to you.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $deliveries->links() }}</div>
        </div>
    </div>
</x-app-layout>