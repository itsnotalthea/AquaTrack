<x-app-layout>
    <x-slot name="title">Inventory | AquaTrack</x-slot>

    <div class="container section">
        <h1 class="mb-1">Inventory thresholds</h1>
        <p class="text-muted mb-4">Low stock is raised when quantity falls to or below the threshold.</p>

        @if (session('status')) <div class="alert alert-success rounded-0">{{ session('status') }}</div> @endif

        <div class="px-card">
            <form method="POST" action="{{ route('admin.inventory.update') }}">
                @csrf
                @method('PUT')

                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th style="width: 160px;">Quantity</th>
                                <th style="width: 200px;">Threshold</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                                <tr>
                                    <td class="text-capitalize">{{ str_replace('_', ' ', $item->name) }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>
                                        <input class="form-control" type="number" min="0" name="thresholds[{{ $item->id }}]"
                                               value="{{ old('thresholds.'.$item->id, $item->threshold) }}" required>
                                        @error('thresholds.'.$item->id) <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </td>
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

                <button class="btn btn-aqua px-4 mt-3" type="submit">Save thresholds</button>
            </form>

            <p class="small text-muted mt-3 mb-0">
                Quantities change automatically when orders are completed, so they are read-only here.
            </p>
        </div>
    </div>
</x-app-layout>