<x-app-layout>
    <x-slot name="title">Containers | AquaTrack</x-slot>

    <div class="container section">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
            <div>
                <h1 class="mb-1">Container status</h1>
                <p class="text-muted mb-0">Aggregate counts by status, plus the latest customer ledger entries.</p>
            </div>
            <a class="btn btn-light" href="{{ route('operations.rentals.index') }}"><x-icon name="calendar" /> Dispenser rentals</a>
        </div>

        <div class="px-card mb-4">
            @include('partials.container-visual')
        </div>

        <div class="row g-4 mb-4">
            @foreach ($ordered as $container)
                <div class="col-md-6 col-lg-4">
                    <div class="px-card h-100">
                        <div class="d-flex justify-content-between align-items-start">
                            <h2 class="h6 text-capitalize mb-1">{{ str_replace('_', ' ', $container->status) }}</h2>
                            <x-icon name="droplet" />
                        </div>
                        <p class="display-5 mb-2">{{ $container->quantity }}</p>
                        <button type="button" class="btn btn-light btn-sm"
                                data-adjust-container
                                data-container-status="{{ $container->status }}"
                                data-container-label="{{ str_replace('_', ' ', $container->status) }}"
                                data-container-quantity="{{ $container->quantity }}"
                                data-container-url="{{ route('operations.containers.adjust', $container->status) }}">
                            <x-icon name="pencil" /> Adjust count
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="px-card">
            <h2 class="h5 mb-3">Recent container ledger</h2>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Date</th><th>Customer</th><th>Type</th><th class="text-end">Quantity</th><th>Recorded by</th></tr></thead>
                    <tbody>
                        @forelse ($transactions as $transaction)
                            <tr>
                                <td class="text-nowrap">{{ $transaction->created_at->format('M d, H:i') }}</td>
                                <td>{{ $transaction->customer?->name ?? '—' }}</td>
                                <td class="text-capitalize">{{ str_replace('_', ' ', $transaction->type) }}</td>
                                <td class="text-end">{{ $transaction->quantity }}</td>
                                <td>{{ $transaction->recorder?->name ?? 'System' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">No container transactions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('modals')
        <div class="modal fade" id="containerAdjustModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" id="containerAdjustForm">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h2 class="modal-title h5">Adjust <span data-container-modal-label></span></h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label" for="containerQuantity">New count</label>
                                <input class="form-control" id="containerQuantity" type="number" name="quantity" min="0" required>
                            </div>
                            <div class="mb-0">
                                <label class="form-label" for="containerReason">Reason</label>
                                <input class="form-control" id="containerReason" name="reason" maxlength="200" required placeholder="Physical count, breakage, return…">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-aqua" type="submit">Save count</button>
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
                    var trigger = event.target.closest('[data-adjust-container]');

                    if (!trigger) {
                        return;
                    }

                    document.querySelector('[data-container-modal-label]').textContent = trigger.dataset.containerLabel;
                    document.getElementById('containerQuantity').value = trigger.dataset.containerQuantity;
                    document.getElementById('containerAdjustForm').action = trigger.dataset.containerUrl;
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('containerAdjustModal')).show();
                });
            })();
        </script>
    @endpush
</x-app-layout>
