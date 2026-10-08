<x-app-layout>
    <x-slot name="title">Inventory | AquaTrack</x-slot>

    <div class="container section">
        <h1 class="mb-1">Inventory</h1>
        <p class="text-muted mb-4">Read-only. Quantities change when orders complete; thresholds are set by the administrator.</p>

        <div class="px-card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Item</th><th class="text-end">Quantity</th><th class="text-end">Threshold</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td class="text-capitalize">{{ str_replace('_', ' ', $item->name) }}</td>
                                <td class="text-end">{{ $item->quantity }}</td>
                                <td class="text-end">{{ $item->threshold }}</td>
                                <td>
                                    @if ($item->isLowStock())
                                        <span class="badge text-bg-danger rounded-0">Low stock</span>
                                    @else
                                        <span class="badge text-bg-success rounded-0">OK</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">No inventory items.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>