<x-app-layout>
    <x-slot name="title">Operations Dashboard | AquaTrack</x-slot>

    <div class="container section">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
            <div>
                <h1 class="mb-1">Operations dashboard</h1>
                <p class="text-muted mb-0">Today at a glance across orders, deliveries, containers, and rentals.</p>
            </div>
            <a class="btn btn-aqua" href="{{ route('operations.orders.index', ['status' => 'pending']) }}">
                <x-icon name="shopping-cart" /> Pending orders
            </a>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-3">
                <a class="text-decoration-none" href="{{ route('operations.orders.index', ['status' => 'pending']) }}">
                    <div class="px-card h-100">
                        <x-icon name="shopping-cart" />
                        <h4 class="h6 mt-2">Pending orders</h4>
                        <p class="display-6 mb-0">{{ $pendingOrders }}</p>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="px-card h-100">
                    <x-icon name="calendar" />
                    <h4 class="h6 mt-2">Today's orders</h4>
                    <p class="display-6 mb-0">{{ $todaysOrders }}</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="px-card h-100">
                    <x-icon name="credit-card" />
                    <h4 class="h6 mt-2">Awaiting payment</h4>
                    <p class="display-6 mb-0">{{ $awaitingPayment }}</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="px-card h-100">
                    <x-icon name="truck" />
                    <h4 class="h6 mt-2">Active deliveries</h4>
                    <p class="display-6 mb-0">{{ $activeDeliveries }}</p>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="px-card h-100 stat-critical">
                    <x-icon name="warning-diamond" />
                    <h4 class="h6 mt-2">Failed deliveries</h4>
                    <p class="display-6 mb-0">{{ $failedDeliveries }}</p>
                    <a class="small" href="{{ route('operations.orders.index', ['status' => 'delivery_failed']) }}">Review</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="px-card h-100">
                    <x-icon name="calendar" />
                    <h4 class="h6 mt-2">Dispensers out</h4>
                    <p class="display-6 mb-0">{{ $activeRentals }}</p>
                </div>
            </div>
            <div class="col-md-4">
                <a class="text-decoration-none" href="{{ route('operations.rentals.index') }}">
                    <div class="px-card h-100 stat-critical">
                        <x-icon name="clock" />
                        <h4 class="h6 mt-2">Overdue rentals</h4>
                        <p class="display-6 mb-0">{{ $overdueRentals }}</p>
                    </div>
                </a>
            </div>
        </div>

        <div class="px-card mb-4">
            <h2 class="h5 mb-3">Container station fill</h2>
            @include('partials.container-visual')
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="px-card h-100 @if($lowStock->isNotEmpty()) stat-critical @endif">
                    <h2 class="h5">Low stock
                        <span class="badge text-bg-danger" data-low-stock-badge>{{ $lowStock->count() }}</span>
                    </h2>
                    <div data-low-stock-list>
                    @if ($lowStock->isEmpty())
                        <p class="mb-0 text-muted">Nothing is below its threshold.</p>
                    @else
                        <ul class="list-unstyled mb-3">
                            @foreach ($lowStock as $item)
                                <li class="d-flex justify-content-between border-bottom py-1">
                                    <span class="text-capitalize">{{ str_replace('_', ' ', $item->name) }}</span>
                                    <span><strong>{{ $item->quantity }}</strong> / {{ $item->threshold }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    </div>
                    <a class="btn btn-aqua px-3" href="{{ route('operations.inventory') }}">Inventory</a>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="px-card h-100">
                    <h2 class="h5">Upcoming deliveries</h2>
                    <div class="calendar-widget" data-calendar-widget data-deliveries="{{ json_encode($upcomingDeliveries ?? []) }}"></div>
                    <a class="btn btn-light w-100 mt-2" href="{{ route('operations.orders.index', ['status' => 'out_for_delivery']) }}">View all</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
