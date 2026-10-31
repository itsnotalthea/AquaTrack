<x-app-layout>
    <x-slot name="title">Order Water | AquaTrack</x-slot>

    <div class="container section" id="order">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
            <div>
                <h1 class="mb-1">Build your order</h1>
                <p class="text-muted mb-0">Add refills, new containers, and dispenser rentals to one order. You can mix pickup and delivery items in the cart.</p>
            </div>
            <a class="btn btn-light" href="{{ route('customer.orders.index') }}">
                <x-icon name="list-box" /> My orders
            </a>
        </div>

        <div class="product-grid">
            @forelse ($products as $product)
                <div class="px-card product-card">
                    <div class="d-flex align-items-start justify-content-between">
                        <x-icon :name="$product->isDispenserRental() ? 'calendar' : ($product->isNewContainer() ? 'shopping-bag' : 'droplet')" />
                        <span class="badge badge-soft text-capitalize">{{ str_replace('_', ' ', $product->resolvedFulfillmentType()) }}</span>
                    </div>
                    <h2 class="h5 mb-0">{{ $product->name }}</h2>
                    <p class="text-muted small mb-0">{{ $product->description }}</p>
                    <div class="price">₱{{ number_format((float) $product->price, 2) }} <small class="text-muted">/ {{ $product->unit }}</small></div>
                    <div class="stock {{ $product->available > 0 ? 'in' : 'out' }}">
                        {{ $product->available >= PHP_INT_MAX ? 'Available' : ($product->available > 0 ? $product->available.' available' : 'Out of stock') }}
                    </div>
                    <button
                        type="button"
                        class="btn btn-aqua"
                        data-add-product
                        data-product-id="{{ $product->id }}"
                        data-product-name="{{ $product->name }}"
                        data-product-price="{{ $product->price }}"
                        data-product-stock="{{ $product->available >= PHP_INT_MAX ? 'Plenty' : $product->available }}"
                        data-product-type="{{ str_replace('_', ' ', $product->resolvedFulfillmentType()) }}"
                        data-product-refill="{{ $product->isRefill() ? '1' : '0' }}"
                        @disabled($product->available < 1)
                    >
                        <x-icon name="plus" /> Add to cart
                    </button>
                </div>
            @empty
                <div class="px-card">
                    <p class="mb-0 text-muted">No products are available right now. Please check back soon.</p>
                </div>
            @endforelse
        </div>
    </div>

    <button type="button" class="cart-fab" data-cart-open aria-label="Open cart">
        <x-icon name="shopping-cart" />
        Cart
        <span class="cart-count {{ $cartCount ? '' : 'd-none' }}" data-cart-count>{{ $cartCount }}</span>
    </button>

    <div class="drawer-scrim" id="cartScrim" aria-hidden="true"></div>

    <aside class="cart-drawer" id="cartDrawer" aria-label="Shopping cart" aria-hidden="true">
        <header>
            <div>
                <h2 class="h5 mb-0">Your cart</h2>
                <small class="text-muted">Prices are confirmed by the station at checkout.</small>
            </div>
            <button type="button" class="icon-button" data-cart-close aria-label="Close cart">
                <x-icon name="close" />
            </button>
        </header>

        <div class="drawer-body">
            <div id="cartItems">
                @forelse ($cartLines as $line)
                    @php $encodedKey = rawurlencode($line['key']); @endphp
                    <div class="cart-line" data-cart-line="{{ $encodedKey }}">
                        <div>
                            <h6>{{ $line['product']->name }}</h6>
                            <div class="meta">
                                @if ($line['container_mode'])
                                    {{ $line['container_mode'] === 'bring_your_own' ? 'Bring your own container' : 'Exchange container' }} ·
                                @endif
                                ₱{{ number_format($line['unit_price'], 2) }} each
                            </div>
                        </div>
                        <div class="line-total">₱{{ number_format($line['subtotal'], 2) }}</div>
                        <div class="qty">
                            <button type="button" data-cart-qty="{{ $encodedKey }}" data-delta="-1" aria-label="Decrease">−</button>
                            <span>{{ $line['quantity'] }}</span>
                            <button type="button" data-cart-qty="{{ $encodedKey }}" data-delta="1" aria-label="Increase">+</button>
                            <button type="button" class="ms-2" data-cart-remove="{{ $encodedKey }}" aria-label="Remove">×</button>
                        </div>
                    </div>
                @empty
                    <p class="text-muted my-3" id="cartEmptyNote">Your cart is empty. Add a product to get started.</p>
                @endforelse
            </div>
        </div>

        <footer>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted">Subtotal</span>
                <strong id="cartSubtotal">₱{{ number_format($cartSubtotal, 2) }}</strong>
            </div>
            <button type="button" class="btn btn-aqua w-100" id="cartCheckout" data-bs-toggle="modal" data-bs-target="#checkoutModal" @disabled(! $cartCount)>
                Checkout
            </button>
            <button type="button" class="btn btn-light w-100 mt-2" data-cart-clear>Clear cart</button>
        </footer>
    </aside>

    @push('modals')
    <!-- Add item modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('customer.cart.store') }}" data-cart-add="{{ route('customer.cart.store') }}">
                    @csrf
                    <input type="hidden" name="product_id" value="">
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title h5 mb-0" data-add-name>Product</h2>
                            <small class="text-muted"><span data-add-type></span> · <span data-add-stock></span></small>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="price mb-2" data-add-price></p>
                        <div class="mb-3">
                            <label class="form-label" for="addQuantity">Quantity</label>
                            <input id="addQuantity" class="form-control" type="number" name="quantity" min="1" value="1" required>
                        </div>
                        <div data-container-choice>
                            <span class="form-label d-block">Container</span>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="container_mode" id="modeExchange" value="exchange" checked>
                                <label class="form-check-label" for="modeExchange">Exchange an empty container for a full one</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="container_mode" id="modeOwn" value="bring_your_own">
                                <label class="form-check-label" for="modeOwn">Bring my own container</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-aqua" type="submit"><x-icon name="shopping-cart" /> Add to cart</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Checkout modal -->
    <div class="modal fade" id="checkoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route('customer.orders.store') }}" class="row g-3" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="use_cart" value="1">
                    <div class="modal-header">
                        <h2 class="modal-title h5">Checkout</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Order type</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="order_type" id="typeDelivery" value="delivery" @checked(old('order_type', 'delivery') === 'delivery')>
                                    <label class="form-check-label" for="typeDelivery">Delivery</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="order_type" id="typePickup" value="pickup" @checked(old('order_type') === 'pickup')>
                                    <label class="form-check-label" for="typePickup">Pickup at the station</label>
                                </div>
                                @error('order_type') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6" id="addressWrap">
                                <label class="form-label" for="delivery_address">Delivery address</label>
                                <input id="delivery_address" class="form-control" name="delivery_address"
                                       value="{{ old('delivery_address', auth()->user()->full_address) }}"
                                       placeholder="House no., street, subdivision">
                                @error('delivery_address') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" for="barangay">Barangay</label>
                                <select id="barangay" class="form-select" name="barangay">
                                    @foreach (\App\Models\Barangay::districts() as $district => $list)
                                        <optgroup label="{{ $district }}">
                                            @foreach ($list as $value => $label)
                                                <option value="{{ $value }}" @selected(old('barangay', auth()->user()->barangay) === $value)>{{ $label }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                @error('barangay') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
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

                            <div class="col-12 d-none" id="paymentDetails">
                                <div class="payment-box">
                                    <p class="payment-title">GCash / Maya payment details</p>
                                    <p class="payment-hint">Send the exact total to the station's GCash or Maya number, then enter the reference number. A screenshot is optional but helps us verify faster.</p>
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <label class="form-label" for="payment_reference">Reference number</label>
                                            <input id="payment_reference" class="form-control" type="text" name="payment_reference" value="{{ old('payment_reference') }}" placeholder="e.g. 091234567890" autocomplete="off">
                                            @error('payment_reference') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="payment_proof">Proof of payment <span class="text-muted fw-normal">(optional)</span></label>
                                            <input id="payment_proof" class="form-control" type="file" name="payment_proof" accept="image/png,image/jpeg">
                                            <div class="form-text" data-payment-file>JPG or PNG, up to 2 MB.</div>
                                            @error('payment_proof') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="notes">Notes</label>
                                <textarea id="notes" class="form-control" name="notes" rows="2">{{ old('notes') }}</textarea>
                                @error('notes') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="weekly_reminder" value="1" id="weekly_reminder" @checked(old('weekly_reminder'))>
                                    <label class="form-check-label" for="weekly_reminder">Remind me for a weekly refill</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Keep shopping</button>
                        <button class="btn btn-aqua" type="submit">Place order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endpush

    @push('scripts')
        <script>
            (function () {
                var addressWrap = document.getElementById('addressWrap');
                var address = document.getElementById('delivery_address');
                var paymentMethod = document.getElementById('payment_method');
                var paymentDetails = document.getElementById('paymentDetails');
                var paymentReference = document.getElementById('payment_reference');
                var paymentProof = document.getElementById('payment_proof');
                var paymentFileName = document.querySelector('[data-payment-file]');

                function refreshPayment() {
                    var method = paymentMethod ? paymentMethod.value : 'cash';
                    var needsPaymentDetails = method === 'gcash' || method === 'maya';

                    if (paymentDetails) {
                        paymentDetails.classList.toggle('d-none', !needsPaymentDetails);
                    }

                    if (paymentReference) {
                        paymentReference.required = needsPaymentDetails;
                    }
                }

                if (paymentProof && paymentFileName) {
                    paymentProof.addEventListener('change', function () {
                        var file = paymentProof.files && paymentProof.files[0];
                        paymentFileName.textContent = file
                            ? file.name
                            : 'JPG or PNG, up to 2 MB.';
                    });
                }

                function refresh() {
                    var checked = document.querySelector('input[name=order_type]:checked');
                    var delivery = checked && checked.value === 'delivery';

                    if (addressWrap) {
                        addressWrap.style.display = delivery ? '' : 'none';
                    }

                    if (address) {
                        address.required = delivery;
                    }

                    refreshPayment();
                }

                Array.prototype.forEach.call(document.querySelectorAll('input[name=order_type]'), function (radio) {
                    radio.addEventListener('change', refresh);
                });

                if (paymentMethod) {
                    paymentMethod.addEventListener('change', refreshPayment);
                }

                refresh();
            })();
        </script>
    @endpush
</x-app-layout>
