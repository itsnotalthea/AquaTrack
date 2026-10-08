<x-app-layout>
    <x-slot name="title">Place an Order | AquaTrack</x-slot>

    <div class="container section" id="order">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h1 class="mb-4">Place an order</h1>

                @if (session('status')) <div class="alert alert-success rounded-0">{{ session('status') }}</div> @endif
                @if ($errors->any()) <div class="alert alert-danger rounded-0">Please check the highlighted fields.</div> @endif

                <form method="POST" action="{{ route('customer.orders.store') }}" data-ajax="{{ route('api.orders.store') }}" class="row g-3 px-card needs-validation" novalidate>
                    @csrf

                    <div class="col-md-6">
                        <label class="form-label" for="cname">Full name</label>
                        <input id="cname" class="form-control" type="text" value="{{ auth()->user()->name }}" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="phone">Mobile number</label>
                        <input id="phone" class="form-control" type="tel" value="{{ auth()->user()->mobile }}" placeholder="09XXXXXXXXX">
                        @error('mobile') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-8">
                        <label class="form-label" for="product_id">Product</label>
                        <select id="product_id" class="form-select" name="product_id" required>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" data-price="{{ $product->price }}" @selected(old('product_id') == $product->id)>
                                    {{ $product->name }} (₱{{ number_format((float) $product->price, 2) }}/{{ $product->unit }})
                                </option>
                            @endforeach
                        </select>
                        @error('product_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="quantity">Quantity</label>
                        <input id="quantity" class="form-control" type="number" name="quantity" min="1" max="{{ $maxQuantity }}"
                               value="{{ old('quantity', 1) }}" required>
                        <div class="form-text">Max {{ $maxQuantity }} per order.</div>
                        @error('quantity') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <span class="form-label d-block">Order type</span>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="order_type" id="m1" value="delivery"
                                   @checked(old('order_type', 'delivery') === 'delivery')>
                            <label class="form-check-label" for="m1">Delivery</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="order_type" id="m2" value="pickup"
                                   @checked(old('order_type') === 'pickup')>
                            <label class="form-check-label" for="m2">Pickup</label>
                        </div>
                        @error('order_type') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12" id="addressWrap">
                        <label class="form-label" for="delivery_address">Delivery address</label>
                        <input id="delivery_address" class="form-control" name="delivery_address"
                               value="{{ old('delivery_address', auth()->user()->full_address) }}"
                               placeholder="House no., street, barangay, Marikina City">
                        @error('delivery_address') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="preferred_date">Preferred date</label>
                        <input id="preferred_date" class="form-control" type="date" name="preferred_date"
                               min="{{ now()->toDateString() }}" value="{{ old('preferred_date', now()->toDateString()) }}" required>
                        @error('preferred_date') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="payment_method">Payment</label>
                        <select id="payment_method" class="form-select" name="payment_method" required>
                            <option value="cash" @selected(old('payment_method') === 'cash')>Cash on delivery / pickup</option>
                            <option value="gcash" @selected(old('payment_method') === 'gcash')>GCash</option>
                            <option value="maya" @selected(old('payment_method') === 'maya')>Maya</option>
                        </select>
                        @error('payment_method') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="container_swap" value="1" id="container_swap"
                                   @checked(old('container_swap'))>
                            <label class="form-check-label" for="container_swap">I want to swap containers</label>
                        </div>
                        <div class="mt-2" id="swapQtyWrap" style="display:none">
                            <label class="form-label" for="container_swap_qty">How many containers to swap?</label>
                            <input id="container_swap_qty" class="form-control" type="number" name="container_swap_qty"
                                   min="1" max="{{ $maxQuantity }}" value="{{ old('container_swap_qty', 1) }}">
                            @error('container_swap_qty') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="weekly_reminder" value="1" id="weekly_reminder"
                                   @checked(old('weekly_reminder'))>
                            <label class="form-check-label" for="weekly_reminder">Remind me for a weekly refill</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="notes">Notes</label>
                        <textarea id="notes" class="form-control" name="notes" rows="2">{{ old('notes') }}</textarea>
                        @error('notes') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <div class="total-box">Total: <span id="total">₱0.00</span></div>
                    </div>

                    <div class="col-12">
                        <button class="btn btn-aqua px-4" type="submit">Place order</button>
                        <a class="btn btn-light rounded-0 fw-bold px-4" href="{{ route('customer.orders.index') }}">My orders</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                var deliveryFee = {{ \App\Services\OrderPricing::deliveryFee() }};
                var product = document.getElementById('product_id');
                var quantity = document.getElementById('quantity');
                var total = document.getElementById('total');
                var addressWrap = document.getElementById('addressWrap');
                var address = document.getElementById('delivery_address');
                var swap = document.getElementById('container_swap');
                var swapWrap = document.getElementById('swapQtyWrap');

                function price() {
                    var option = product.options[product.selectedIndex];
                    return parseFloat(option.dataset.price) || 0;
                }

                function isDelivery() {
                    var checked = document.querySelector('input[name=order_type]:checked');
                    return checked && checked.value === 'delivery';
                }

                function money(value) {
                    return '₱' + value.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
                }

                function refresh() {
                    var qty = parseInt(quantity.value, 10) || 0;
                    var sum = price() * qty;
                    if (isDelivery()) sum += deliveryFee;
                    total.textContent = money(sum);
                    addressWrap.style.display = isDelivery() ? '' : 'none';
                    address.required = isDelivery();
                    swapWrap.style.display = swap.checked ? '' : 'none';
                }

                product.addEventListener('change', refresh);
                quantity.addEventListener('input', refresh);
                swap.addEventListener('change', refresh);
                Array.prototype.forEach.call(document.querySelectorAll('input[name=order_type]'), function (radio) {
                    radio.addEventListener('change', refresh);
                });
                refresh();
            })();
        </script>
    @endpush
</x-app-layout>