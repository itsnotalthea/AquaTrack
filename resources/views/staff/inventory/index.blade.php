<x-app-layout>
    <x-slot name="title">Inventory | AquaTrack</x-slot>

    <div class="container section">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
            <div>
                <h1 class="mb-1">Inventory</h1>
                <p class="text-muted mb-0">Quantities change automatically as orders complete. Use an adjustment only for physical counts, damage, or corrections.</p>
            </div>
            <a class="btn btn-light" href="{{ route('operations.containers') }}"><x-icon name="droplet" /> Container counts</a>
        </div>

        <div class="px-card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Item</th><th class="text-end">Quantity</th><th class="text-end">Threshold</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td class="text-capitalize">{{ str_replace('_', ' ', $item->name) }}</td>
                                <td class="text-end">{{ $item->quantity }}</td>
                                <td class="text-end">{{ $item->threshold }}</td>
                                <td>
                                    @if ($item->isLowStock())
                                        <span class="badge text-bg-danger">Low stock</span>
                                    @else
                                        <span class="badge text-bg-success">OK</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-light"
                                            data-adjust-item
                                            data-item-label="{{ str_replace('_', ' ', $item->name) }}"
                                            data-item-quantity="{{ $item->quantity }}"
                                            data-item-url="{{ route('operations.inventory.adjust', $item) }}">
                                        <x-icon name="pencil" /> Adjust
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">No inventory items.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('modals')
        <div class="modal fade" id="inventoryAdjustModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" id="inventoryAdjustForm">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h2 class="modal-title h5">Adjust <span data-item-modal-label></span></h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label" for="inventoryQuantity">New quantity</label>
                                <input class="form-control" id="inventoryQuantity" type="number" name="quantity" min="0" required>
                            </div>
                            <div class="mb-0">
                                <label class="form-label" for="inventoryReason">Reason</label>
                                <input class="form-control" id="inventoryReason" name="reason" maxlength="200" required placeholder="Physical count, damage, correction…">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-aqua" type="submit">Save adjustment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endpush

    @push('scripts')
        <script>
            (function () {
                document.addEventListener('click', function (event) {
                    var trigger = event.target.closest('[data-adjust-item]');

                    if (!trigger) {
                        return;
                    }

                    document.querySelector('[data-item-modal-label]').textContent = trigger.dataset.itemLabel;
                    document.getElementById('inventoryQuantity').value = trigger.dataset.itemQuantity;
                    document.getElementById('inventoryAdjustForm').action = trigger.dataset.itemUrl;
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('inventoryAdjustModal')).show();
                });
            })();
        </script>
    @endpush
</x-app-layout>
