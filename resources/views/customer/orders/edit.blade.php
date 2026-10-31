<x-app-layout>
    <x-slot name="title">Edit Order #{{ $order->id }} | AquaTrack</x-slot>

    <div class="container section">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div>
                <h1 class="h4 mb-1">Edit order #{{ $order->id }}</h1>
                <p class="text-muted mb-0">You can edit this order while it is still pending.</p>
            </div>
            <a class="btn btn-light" href="{{ route('customer.orders.show', $order) }}">Cancel</a>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <p class="fw-bold mb-1">Please review the following:</p>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('customer.orders.update', $order) }}">
            @csrf
            @method('PUT')

            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="px-card">
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                            <h2 class="h6 mb-0">Items</h2>
                            <button class="btn btn-sm btn-light" type="button" id="addOrderItem">Add item</button>
                        </div>

                        <div id="orderItems">
                            @foreach ($editItems as $index => $item)
                                <div class="row g-2 align-items-end border-bottom pb-3 mb-3" data-order-item>
                                    <div class="col-md-6">
                                        <label class="form-label" for="product_{{ $index }}">Product</label>
                                        <select class="form-select" id="product_{{ $index }}" name="items[{{ $index }}][product_id]" data-product-select required>
                                            <option value="">Choose a product</option>
                                            @foreach ($products as $product)
                                                <option value="{{ $product->id }}" data-refill="{{ $product->isRefill() ? '1' : '0' }}"
                                                    @selected((int) ($item['product_id'] ?? 0) === (int) $product->id)>
                                                    {{ $product->name }}{{ $product->is_active ? '' : ' (unavailable)' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <label class="form-label" for="quantity_{{ $index }}">Quantity</label>
                                        <input class="form-control" id="quantity_{{ $index }}" type="number" min="1" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" required>
                                    </div>
                                    <div class="col-6 col-md-3" data-container-wrap>
                                        <label class="form-label" for="container_{{ $index }}">Container</label>
                                        <select class="form-select" id="container_{{ $index }}" name="items[{{ $index }}][container_mode]" data-container-select>
                                            <option value="exchange" @selected(($item['container_mode'] ?? null) === 'exchange')>Exchange</option>
                                            <option value="bring_your_own" @selected(($item['container_mode'] ?? null) === 'bring_your_own')>Bring my own</option>
                                        </select>
                                    </div>
                                    <div class="col-md-1">
                                        <button class="btn btn-sm btn-light w-100" type="button" data-remove-item aria-label="Remove item">×</button>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <template id="orderItemTemplate">
                            <div class="row g-2 align-items-end border-bottom pb-3 mb-3" data-order-item>
                                <div class="col-md-6">
                                    <label class="form-label" for="product___INDEX__">Product</label>
                                    <select class="form-select" id="product___INDEX__" name="items[__INDEX__][product_id]" data-product-select required>
                                        <option value="">Choose a product</option>
                                        @foreach ($products->where('is_active', true) as $product)
                                            <option value="{{ $product->id }}" data-refill="{{ $product->isRefill() ? '1' : '0' }}">{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6 col-md-2">
                                    <label class="form-label" for="quantity___INDEX__">Quantity</label>
                                    <input class="form-control" id="quantity___INDEX__" type="number" min="1" name="items[__INDEX__][quantity]" value="1" required>
                                </div>
                                <div class="col-6 col-md-3" data-container-wrap>
                                    <label class="form-label" for="container___INDEX__">Container</label>
                                    <select class="form-select" id="container___INDEX__" name="items[__INDEX__][container_mode]" data-container-select>
                                        <option value="exchange">Exchange</option>
                                        <option value="bring_your_own">Bring my own</option>
                                    </select>
                                </div>
                                <div class="col-md-1">
                                    <button class="btn btn-sm btn-light w-100" type="button" data-remove-item aria-label="Remove item">×</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="px-card">
                        <h2 class="h6 mb-3">Order details</h2>

                        <div class="mb-3">
                            <label class="form-label" for="order_type">Fulfillment</label>
                            <select class="form-select" id="order_type" name="order_type" required>
                                <option value="delivery" @selected(old('order_type', $order->order_type) === 'delivery')>Delivery</option>
                                <option value="pickup" @selected(old('order_type', $order->order_type) === 'pickup')>Pickup</option>
                            </select>
                        </div>

                        <div class="mb-3" id="deliveryAddressWrap">
                            <label class="form-label" for="delivery_address">Delivery address</label>
                            <input class="form-control" id="delivery_address" name="delivery_address" value="{{ old('delivery_address', $order->delivery_address) }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="barangay">Barangay</label>
                            <select class="form-select" id="barangay" name="barangay">
                                <option value="">Choose a barangay</option>
                                @foreach ($barangays as $district => $list)
                                    <optgroup label="{{ $district }}">
                                        @foreach ($list as $value => $label)
                                            <option value="{{ $value }}" @selected(old('barangay', $order->barangay) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="preferred_date">Preferred date</label>
                            <input class="form-control" id="preferred_date" type="date" name="preferred_date" min="{{ now()->toDateString() }}" value="{{ old('preferred_date', $order->preferred_date?->toDateString()) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="notes">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $order->notes) }}</textarea>
                        </div>

                        <button class="btn btn-aqua w-100" type="submit">Save changes</button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            (function () {
                var rows = document.getElementById('orderItems');
                var template = document.getElementById('orderItemTemplate');
                var nextIndex = {{ count($editItems) }};
                var orderType = document.getElementById('order_type');
                var addressWrap = document.getElementById('deliveryAddressWrap');
                var address = document.getElementById('delivery_address');

                function refreshRow(row) {
                    var select = row.querySelector('[data-product-select]');
                    var selected = select && select.options[select.selectedIndex];
                    var showContainer = selected && selected.dataset.refill === '1';
                    var wrap = row.querySelector('[data-container-wrap]');
                    var containerSelect = row.querySelector('[data-container-select]');

                    wrap.classList.toggle('d-none', !showContainer);
                    if (!showContainer && containerSelect) {
                        containerSelect.value = '';
                    } else if (showContainer && containerSelect && !containerSelect.value) {
                        containerSelect.value = 'exchange';
                    }
                }

                function refreshAddress() {
                    var delivery = orderType.value === 'delivery';
                    addressWrap.classList.toggle('d-none', !delivery);
                    address.required = delivery;
                }

                rows.addEventListener('change', function (event) {
                    if (event.target.matches('[data-product-select]')) {
                        refreshRow(event.target.closest('[data-order-item]'));
                    }
                });

                rows.addEventListener('click', function (event) {
                    var remove = event.target.closest('[data-remove-item]');
                    if (remove) {
                        remove.closest('[data-order-item]').remove();
                    }
                });

                document.getElementById('addOrderItem').addEventListener('click', function () {
                    var html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex++));
                    rows.insertAdjacentHTML('beforeend', html);
                    refreshRow(rows.lastElementChild);
                });

                orderType.addEventListener('change', refreshAddress);
                rows.querySelectorAll('[data-order-item]').forEach(refreshRow);
                refreshAddress();
            })();
        </script>
    @endpush
</x-app-layout>
