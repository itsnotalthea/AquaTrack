<x-app-layout>
    <x-slot name="title">Dispenser Rentals | AquaTrack</x-slot>

    <div class="container section">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
            <div>
                <h1 class="mb-1">Dispenser rentals</h1>
                <p class="text-muted mb-0">Track active rentals, due dates, and partial returns. Good units return to available stock.</p>
            </div>
            <form method="GET" class="d-flex gap-2">
                <select class="form-select" name="status" aria-label="Rental status">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn btn-aqua" type="submit">Filter</button>
            </form>
        </div>

        <div class="px-card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr><th>Order</th><th>Customer</th><th>Product</th><th class="text-end">Rented</th><th class="text-end">Returned</th><th>Due</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse ($rentals as $rental)
                            @php
                                $overdue = $rental->status === \App\Models\DispenserRental::STATUS_ACTIVE && $rental->due_at?->isPast();
                            @endphp
                            <tr>
                                <td>#{{ $rental->order_id }}</td>
                                <td>{{ $rental->customer?->name ?? '—' }}</td>
                                <td>{{ $rental->product?->name ?? 'Dispenser' }}</td>
                                <td class="text-end">{{ $rental->quantity }}</td>
                                <td class="text-end">{{ $rental->returned_quantity }}</td>
                                <td class="text-nowrap">
                                    {{ $rental->due_at?->format('M d, Y') ?? '—' }}
                                    @if ($overdue)
                                        <span class="badge text-bg-danger ms-1">Overdue</span>
                                    @endif
                                </td>
                                <td class="text-capitalize">
                                    <span class="badge badge-soft">{{ $overdue ? 'overdue' : $rental->status }}</span>
                                </td>
                                <td class="text-end">
                                    @if ($rental->remaining > 0)
                                        <button type="button" class="btn btn-sm btn-aqua"
                                                data-return-rental
                                                data-rental-label="Order #{{ $rental->order_id }} — {{ $rental->customer?->name }}"
                                                data-rental-remaining="{{ $rental->remaining }}"
                                                data-rental-url="{{ route('operations.rentals.return', $rental) }}">
                                            <x-icon name="reload" /> Return
                                        </button>
                                    @else
                                        <span class="text-muted small">Complete</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-muted">No dispenser rentals match this filter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $rentals->links() }}</div>
        </div>
    </div>

    @push('modals')
        <div class="modal fade" id="rentalReturnModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" id="rentalReturnForm">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h2 class="modal-title h5">Return dispenser</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small" data-rental-modal-label></p>
                            <div class="mb-3">
                                <label class="form-label" for="rentalQuantity">Quantity returned</label>
                                <input class="form-control" id="rentalQuantity" type="number" name="quantity" min="1" required>
                                <div class="form-text" data-rental-remaining-label></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="rentalCondition">Condition</label>
                                <select class="form-select" id="rentalCondition" name="condition" required>
                                    <option value="good">Good — back to available stock</option>
                                    <option value="damaged">Damaged — send to maintenance</option>
                                    <option value="lost">Lost — write off</option>
                                </select>
                            </div>
                            <div class="mb-0">
                                <label class="form-label" for="rentalNotes">Notes</label>
                                <textarea class="form-control" id="rentalNotes" name="notes" rows="2" maxlength="500"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-aqua" type="submit">Record return</button>
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
                    var trigger = event.target.closest('[data-return-rental]');

                    if (!trigger) {
                        return;
                    }

                    var remaining = parseInt(trigger.dataset.rentalRemaining, 10) || 1;

                    document.querySelector('[data-rental-modal-label]').textContent = trigger.dataset.rentalLabel;
                    document.querySelector('[data-rental-remaining-label]').textContent = remaining + ' unit(s) still out on this rental.';
                    document.getElementById('rentalQuantity').max = remaining;
                    document.getElementById('rentalQuantity').value = remaining;
                    document.getElementById('rentalReturnForm').action = trigger.dataset.rentalUrl;
                    bootstrap.Modal.getOrCreateInstance(document.getElementById('rentalReturnModal')).show();
                });
            })();
        </script>
    @endpush
</x-app-layout>
