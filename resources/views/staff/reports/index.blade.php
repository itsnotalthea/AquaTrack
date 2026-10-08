<x-app-layout>
    <x-slot name="title">{{ $title }} | AquaTrack</x-slot>

    <div class="container section">
        <h1 class="mb-1">Reports</h1>
        <p class="text-muted mb-4">{{ $title }}</p>

        <div class="row g-4 align-items-start">
            <div class="col-lg-3">
                <div class="px-card">
                    <nav class="d-grid gap-1">
                        @foreach ($allTypes as $key => $label)
                            <a class="btn {{ $key === $type ? 'btn-aqua' : 'btn-light rounded-0 fw-bold' }} text-start"
                               href="{{ route('staff.reports.index', ['type' => $key]) }}">{{ $label }}</a>
                        @endforeach
                    </nav>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="px-card mb-4">
                    <form method="GET" class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label" for="from">From</label>
                            <input class="form-control" type="date" id="from" name="from" value="{{ $filters['from'] }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="to">To</label>
                            <input class="form-control" type="date" id="to" name="to" value="{{ $filters['to'] }}">
                        </div>

                        @if (in_array($type, ['sales', 'orders', 'sales_by_product'], true))
                            <div class="col-md-3">
                                <label class="form-label" for="status">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All</option>
                                    @foreach ($options['statuses'] as $key => $label)
                                        <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @if (in_array($type, ['sales', 'orders'], true))
                            <div class="col-md-3">
                                <label class="form-label" for="order_type">Order type</label>
                                <select class="form-select" id="order_type" name="order_type">
                                    <option value="">All</option>
                                    @foreach ($options['orderTypes'] as $key => $label)
                                        <option value="{{ $key }}" @selected($filters['order_type'] === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="payment_status">Payment</label>
                                <select class="form-select" id="payment_status" name="payment_status">
                                    <option value="">All</option>
                                    @foreach ($options['paymentStatuses'] as $key => $label)
                                        <option value="{{ $key }}" @selected($filters['payment_status'] === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="payment_method">Method</label>
                                <select class="form-select" id="payment_method" name="payment_method">
                                    <option value="">All</option>
                                    @foreach ($options['paymentMethods'] as $key => $label)
                                        <option value="{{ $key }}" @selected($filters['payment_method'] === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @if (in_array($type, ['deliveries'], true))
                            <div class="col-md-3">
                                <label class="form-label" for="delivery_status">Delivery status</label>
                                <select class="form-select" id="delivery_status" name="status">
                                    <option value="">All</option>
                                    @foreach ($options['deliveryStatuses'] as $key => $label)
                                        <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @if (in_array($type, ['deliveries', 'sales', 'orders'], true))
                            <div class="col-md-3">
                                <label class="form-label" for="driver_id">Driver</label>
                                <select class="form-select" id="driver_id" name="driver_id">
                                    <option value="">All</option>
                                    @foreach ($options['drivers'] as $id => $name)
                                        <option value="{{ $id }}" @selected((string) $filters['driver_id'] === (string) $id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @if (in_array($type, ['sales_by_product', 'sales', 'orders'], true))
                            <div class="col-md-3">
                                <label class="form-label" for="product_id">Product</label>
                                <select class="form-select" id="product_id" name="product_id">
                                    <option value="">All</option>
                                    @foreach ($options['products'] as $id => $name)
                                        <option value="{{ $id }}" @selected((string) $filters['product_id'] === (string) $id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @if (in_array($type, ['sales', 'orders', 'customers', 'containers'], true))
                            <div class="col-md-3">
                                <label class="form-label" for="customer_id">Customer</label>
                                <select class="form-select" id="customer_id" name="customer_id">
                                    <option value="">All</option>
                                    @foreach ($options['customers'] as $id => $name)
                                        <option value="{{ $id }}" @selected((string) $filters['customer_id'] === (string) $id)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @if (in_array($type, ['sales', 'orders', 'customers', 'deliveries'], true))
                            <div class="col-md-3">
                                <label class="form-label" for="barangay">Barangay</label>
                                <select class="form-select" id="barangay" name="barangay">
                                    <option value="">All</option>
                                    @foreach ($options['barangays'] as $name)
                                        <option value="{{ $name }}" @selected($filters['barangay'] === $name)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @if ($type === 'inventory')
                            <div class="col-md-3">
                                <label class="form-label" for="container_status">Container status</label>
                                <select class="form-select" id="container_status" name="container_status">
                                    <option value="">Stock items</option>
                                    @foreach ($options['containerStatuses'] as $key => $label)
                                        <option value="{{ $key }}" @selected($filters['container_status'] === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="low_stock">Low stock only</label>
                                <select class="form-select" id="low_stock" name="low_stock">
                                    <option value="0" @selected(! $filters['low_stock'])>No</option>
                                    <option value="1" @selected($filters['low_stock'])>Yes</option>
                                </select>
                            </div>
                        @endif

                        <div class="col-12 d-flex gap-2">
                            <button class="btn btn-aqua px-4" type="submit">Apply filters</button>
                            <a class="btn btn-light rounded-0 fw-bold px-4" href="{{ route('staff.reports.index', ['type' => $type]) }}">Reset</a>
                        </div>
                    </form>
                </div>

                @if (! empty($totals))
                    <div class="px-card mb-4">
                        <div class="row g-3">
                            @foreach ($totals as $key => $value)
                                <div class="col-6 col-md-3">
                                    <div class="small text-muted text-capitalize">{{ str_replace('_', ' ', $key) }}</div>
                                    <div class="h5 mb-0">
                                        @if (in_array($key, ['gross', 'collected', 'amount', 'spent', 'total'], true))
                                            ₱{{ number_format((float) $value, 2) }}
                                        @else
                                            {{ $value }}
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="px-card">
                    @if (empty($rows))
                        <p class="text-muted mb-0">No data for these filters.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        @foreach ($columns as $label)
                                            <th>{{ $label }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rows as $row)
                                        <tr>
                                            @foreach (array_keys($columns) as $key)
                                                <td>
                                                    @php $value = $row[$key] ?? ''; @endphp
                                                    @if (in_array($key, ['total', 'amount', 'spent'], true))
                                                        ₱{{ number_format((float) $value, 2) }}
                                                    @elseif (is_bool($value))
                                                        {{ $value ? 'Yes' : 'No' }}
                                                    @else
                                                        {{ is_string($value) ? str_replace('_', ' ', $value) : $value }}
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if ($type === 'containers' && ! empty($transactions))
                        <h2 class="h5 mt-4">Container transactions</h2>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead><tr><th>Date</th><th>Customer</th><th>Type</th><th class="text-end">Quantity</th><th>Order</th></tr></thead>
                                <tbody>
                                    @foreach ($transactions as $t)
                                        <tr>
                                            <td>{{ $t['date'] }}</td>
                                            <td>{{ $t['customer'] }}</td>
                                            <td class="text-capitalize">{{ $t['type'] }}</td>
                                            <td class="text-end">{{ $t['quantity'] }}</td>
                                            <td>{{ $t['order'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>