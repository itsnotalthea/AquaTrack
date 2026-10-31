<x-app-layout>
    <x-slot name="title">Order #{{ $order->id }} | AquaTrack</x-slot>

    <div class="container section ops-order">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <a class="btn btn-light btn-sm px-3" href="{{ route('operations.orders.index') }}">&larr; Orders</a>
            <span class="badge badge-soft text-capitalize">{{ str_replace('_', ' ', $order->status) }}</span>
        </div>

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="px-card compact mb-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 mb-2">
                        <h1 class="h5 mb-0">Order #{{ $order->id }}</h1>
                        <span class="small text-muted">{{ $order->created_at->format('M d, Y g:i A') }}</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Product</th><th>Fulfillment</th><th class="text-end">Qty</th><th class="text-end">Price</th><th class="text-end">Subtotal</th></tr></thead>
                            <tbody>
                                @foreach ($order->items as $item)
                                    <tr>
                                        <td>{{ $item->resolvedName() }}</td>
                                        <td class="small text-muted">
                                            {{ str_replace('_', ' ', $item->resolvedFulfillmentType()) }}
                                            @if ($item->resolvedContainerMode())
                                                · {{ $item->resolvedContainerMode() === 'bring_your_own' ? 'bring your own' : 'exchange' }}
                                            @endif
                                        </td>
                                        <td class="text-end">{{ $item->quantity }}</td>
                                        <td class="text-end">₱{{ number_format((float) $item->price, 2) }}</td>
                                        <td class="text-end">₱{{ number_format((float) $item->subtotal, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between mt-2 small">
                        <span class="text-muted">Delivery fee</span>
                        <span>₱{{ number_format((float) $order->delivery_fee, 2) }}</span>
                    </div>
                    <div class="total-box mt-2 px-3 py-2 text-end">Total: ₱{{ number_format((float) $order->total_amount, 2) }}</div>
                </div>

                <div class="px-card compact mb-3">
                    <h2 class="h6">Next step</h2>
                    @php $next = $order->allowedTransitions(); @endphp

                    @if (empty($next))
                        <p class="mb-0 text-muted small">This order is {{ str_replace('_', ' ', $order->status) }} and has no further transitions.</p>
                    @else
                        @php
                            $statusActions = [
                                'confirmed' => ['Confirm order', 'Accept this order and move it into the fulfillment queue.'],
                                'preparing' => ['Start preparing', 'Mark that the station is preparing this order.'],
                                'out_for_delivery' => ['Dispatch order', 'Start the delivery run. The order needs an assigned driver.'],
                                'delivered' => ['Mark delivered', 'Record that the customer received the order.'],
                                'delivery_failed' => ['Report delivery issue', 'Record an unsuccessful delivery and provide a reason.'],
                                'ready_for_pickup' => ['Ready for pickup', 'Let the customer know the pickup is ready.'],
                                'picked_up' => ['Mark picked up', 'Record that the customer collected the order.'],
                                'completed' => ['Complete order', 'Close the order after fulfillment is finished.'],
                                'cancelled' => ['Cancel order', 'Cancel this order. This cannot be undone.'],
                            ];
                        @endphp
                        <p class="small text-muted mb-2">Choose the action that matches what has happened:</p>
                        <div class="status-actions">
                            @foreach ($next as $status)
                                @php
                                    [$actionLabel, $actionDescription] = $statusActions[$status] ?? [str_replace('_', ' ', ucfirst($status)), 'Update the order status.'];
                                    $isDanger = $status === \App\Models\Order::STATUS_CANCELLED;
                                    $isFailure = $status === \App\Models\Order::STATUS_DELIVERY_FAILED;
                                @endphp
                                <form method="POST" action="{{ route('operations.orders.status', $order) }}"
                                      data-ajax-post @if ($isDanger) data-confirm-submit="Cancel this order?" @endif
                                      class="status-action {{ $isDanger ? 'status-action-danger' : '' }}">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="status" value="{{ $status }}">
                                    <div class="status-action-copy">
                                        <strong>{{ $actionLabel }}</strong>
                                        <span>{{ $actionDescription }}</span>
                                    </div>
                                    @if ($isFailure)
                                        <div class="mt-2">
                                            <label class="form-label" for="failure_reason">Reason for failed delivery</label>
                                            <input class="form-control form-control-sm" id="failure_reason" name="failure_reason"
                                                   value="{{ old('failure_reason') }}" maxlength="500" required>
                                        </div>
                                    @endif
                                    <button class="btn {{ $isDanger ? 'btn-outline-danger' : 'btn-aqua' }} btn-sm px-3 mt-2"
                                            type="submit">{{ $isDanger ? 'Cancel order' : $actionLabel }}</button>
                                </form>
                            @endforeach
                        </div>
                        @error('status') <div class="invalid-feedback d-block mt-2">{{ $message }}</div> @enderror
                        @error('failure_reason') <div class="invalid-feedback d-block mt-2">{{ $message }}</div> @enderror
                    @endif
                </div>

                @if ($order->changeLogs->isNotEmpty())
                    <div class="px-card compact mb-3">
                        <h2 class="h6 mb-2">Customer changes</h2>
                        @foreach ($order->changeLogs as $changeLog)
                            <div class="border-bottom py-2 small">
                                <div class="d-flex justify-content-between gap-3">
                                    <strong>{{ $changeLog->user?->name ?? 'Customer' }}</strong>
                                    <span class="text-muted text-nowrap">{{ $changeLog->created_at->format('M d, Y g:i A') }}</span>
                                </div>
                                @foreach ($changeLog->changes as $label => $change)
                                    <div class="mt-1">
                                        <span class="text-muted">{{ $label }}:</span>
                                        <span>{{ $change['old'] }}</span>
                                        <x-icon name="arrow-right" />
                                        <span>{{ $change['new'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="px-card compact">
                    <h2 class="h6 mb-2">Status history</h2>
                    @forelse ($order->statusLogs as $log)
                        <div class="d-flex justify-content-between gap-3 border-bottom status-log">
                            <span>
                                <strong class="text-capitalize">{{ str_replace('_', ' ', $log->from_status ?? 'new') }}</strong>
                                <x-icon name="arrow-right" />
                                <strong class="text-capitalize">{{ str_replace('_', ' ', $log->to_status) }}</strong>
                                @if ($log->notes)
                                    <span class="text-muted">— {{ $log->notes }}</span>
                                @endif
                            </span>
                            <span class="text-muted text-nowrap">{{ $log->created_at->format('M d, H:i') }}</span>
                        </div>
                    @empty
                        <p class="mb-0 text-muted small">No status changes recorded yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="col-lg-4">
                <div class="px-card compact mb-3">
                    <h2 class="h6">Customer</h2>
                    <dl class="meta-list">
                        <dt>Name</dt>
                        <dd>{{ $order->customer?->name ?? '—' }}</dd>
                        <dt>Email</dt>
                        <dd>{{ $order->customer?->email ?? '—' }}</dd>
                        <dt>Mobile</dt>
                        <dd>{{ $order->customer?->mobile ?? '—' }}</dd>
                    </dl>
                </div>

                <div class="px-card compact mb-3">
                    <h2 class="h6">Order</h2>
                    <dl class="meta-list">
                        <dt>Type</dt>
                        <dd class="text-capitalize">{{ $order->order_type }}</dd>
                        @if ($order->delivery_address)
                            <dt>Address</dt>
                            <dd>{{ $order->delivery_address }}</dd>
                        @endif
                        @if ($order->barangay)
                            <dt>Barangay</dt>
                            <dd>{{ $order->barangay }}</dd>
                        @endif
                        @if ($order->preferred_date)
                            <dt>Preferred</dt>
                            <dd>{{ $order->preferred_date->format('M d, Y') }}</dd>
                        @endif
                        @if ($order->container_swap)
                            <dt>Swap</dt>
                            <dd>{{ $order->container_swap_qty }} container(s)</dd>
                        @endif
                        @if ($order->notes)
                            <dt>Notes</dt>
                            <dd>{{ $order->notes }}</dd>
                        @endif
                    </dl>
                </div>

                @if ($order->isDelivery())
                    <div class="px-card compact mb-3">
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <h2 class="h6 mb-0">Driver</h2>
                            @if ($order->delivery)
                                <span class="badge badge-soft text-capitalize">{{ str_replace('_', ' ', $order->delivery->status) }}</span>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('operations.orders.driver', $order) }}" class="d-flex align-items-end gap-2 mt-2">
                            @csrf
                            @method('PUT')
                            <div class="flex-grow-1">
                                <label class="form-label" for="driver_id">Assign driver</label>
                                <select class="form-select form-select-sm" id="driver_id" name="driver_id" required>
                                    @foreach ($drivers as $driver)
                                        <option value="{{ $driver->id }}" @selected($order->delivery?->driver_id === $driver->id)>{{ $driver->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <button class="btn btn-aqua btn-sm px-3" type="submit">{{ $order->delivery ? 'Reassign' : 'Assign' }}</button>
                        </form>
                    </div>
                @endif

                <div class="px-card compact mb-3">
                    <h2 class="h6">Payment</h2>
                    @if ($order->isPaid())
                        <p class="mb-0 small">
                            Paid via <span class="text-capitalize">{{ $order->payment_method }}</span>
                            @if ($order->payment)
                                <br><span class="text-muted">by {{ $order->payment->recorder?->name }}</span>
                            @endif
                        </p>
                    @else
                        <p class="small text-muted">
                            Records the full order total of ₱{{ number_format((float) $order->total_amount, 2) }}.
                            No partial payments.
                        </p>
                        <button type="button" class="btn btn-aqua btn-sm w-100" data-payment-order="{{ $order->id }}"
                                data-payment-url="{{ route('operations.orders.payment', $order) }}"
                                data-payment-label="{{ $order->customer?->name }} — ₱{{ number_format((float) $order->total_amount, 2) }}">
                            Record payment
                        </button>
                    @endif

                    @if ($order->payment_reference)
                        <div class="mt-2 small">
                            <div class="text-muted">Reference</div>
                            <div class="text-break">{{ $order->payment_reference }}</div>
                        </div>
                    @endif

                    @if ($order->payment_proof_path)
                        <div class="mt-2">
                            <div class="text-muted small">Proof</div>
                            <a href="{{ Storage::disk('public')->url($order->payment_proof_path) }}" target="_blank" rel="noopener">
                                <img src="{{ Storage::disk('public')->url($order->payment_proof_path) }}" alt="Payment proof" class="img-fluid rounded border mt-1" style="max-height: 96px;">
                            </a>
                        </div>
                    @endif
                </div>

                @if ($order->dispenserRentals->isNotEmpty())
                    <div class="px-card compact">
                        <h2 class="h6">Dispenser rentals</h2>
                        @foreach ($order->dispenserRentals as $rental)
                            <div class="border-bottom py-2 small">
                                <strong>{{ $rental->quantity }} unit(s)</strong>
                                <span class="text-capitalize">· {{ $rental->status }}</span>
                                <br>
                                <span class="text-muted">Due {{ $rental->due_at?->format('M d, Y') }} · returned {{ $rental->returned_quantity }}</span>
                            </div>
                        @endforeach
                        <a class="btn btn-light btn-sm w-100 mt-2" href="{{ route('operations.rentals.index') }}">Manage rentals</a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('modals')
        @if (! $order->isPaid())
            <div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST">
                            @csrf
                            <div class="modal-header">
                                <h2 class="modal-title h5">Record payment</h2>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="mb-2" data-payment-label></p>
                                <label class="form-label" for="paymentMethod">Method</label>
                                <select class="form-select" id="paymentMethod" name="method" required>
                                    <option value="cash">Cash</option>
                                    <option value="gcash">GCash</option>
                                    <option value="maya">Maya</option>
                                </select>
                                <p class="small text-muted mt-2 mb-0">
                                    Payments are full amount only. Drivers never record payments.
                                </p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button class="btn btn-aqua" type="submit">Save payment</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endpush

    @push('scripts')
        <script>
            document.querySelectorAll('form[data-confirm-submit]').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!window.confirm(form.dataset.confirmSubmit)) {
                        event.preventDefault();
                        event.stopImmediatePropagation();
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>
