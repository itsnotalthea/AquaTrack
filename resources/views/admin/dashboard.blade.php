<x-app-layout>
    <x-slot name="title">Admin Dashboard | AquaTrack</x-slot>

    <div class="container section">
        <h1 class="mb-4">Admin Dashboard</h1>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="px-card">
                    <div class="ico">🧾</div>
                    <h4 class="h6 mt-2">Open orders</h4>
                    <p class="display-6 mb-0">{{ $pendingOrders }}</p>
                    <a class="small" href="{{ route('staff.orders.index') }}">View orders</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="px-card">
                    <div class="ico">📦</div>
                    <h4 class="h6 mt-2">Low stock items</h4>
                    <p class="display-6 mb-0">{{ $lowStock->count() }}</p>
                    @if ($lowStock->isNotEmpty())
                        <span class="badge text-bg-danger rounded-0">Needs restock</span>
                    @else
                        <span class="badge text-bg-success rounded-0">All stocked</span>
                    @endif
                </div>
            </div>
            <div class="col-md-4">
                <div class="px-card">
                    <div class="ico">💰</div>
                    <h4 class="h6 mt-2">Sales today</h4>
                    <p class="display-6 mb-0">₱{{ number_format($salesToday, 2) }}</p>
                    <span class="small text-muted">{{ $orderCountToday }} order(s) today</span>
                </div>
            </div>
        </div>

        <div class="px-card mb-4">
            <h2 class="h5 mb-3">Container status</h2>
            @include('partials.container-visual')
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="px-card">
                    <h2 class="h5">Low stock
                        <span class="badge text-bg-danger rounded-0" data-low-stock-badge>{{ $lowStock->count() }}</span>
                    </h2>
                    <div data-low-stock-list>
                    @if ($lowStock->isEmpty())
                        <p class="mb-0 text-muted">Everything is above its threshold.</p>
                    @else
                        <ul class="list-unstyled mb-0">
                            @foreach ($lowStock as $item)
                                <li class="d-flex justify-content-between border-bottom py-1">
                                    <span class="text-capitalize">{{ str_replace('_', ' ', $item->name) }}</span>
                                    <span><strong>{{ $item->quantity }}</strong> / {{ $item->threshold }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    </div>
                    <a class="btn btn-aqua px-3 mt-3" href="{{ route('admin.inventory.index') }}">Inventory settings</a>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="px-card">
                    <h2 class="h5">Manage</h2>
                    <div class="row g-2 mt-1">
                        <div class="col-6">
                            <a class="btn btn-light rounded-0 fw-bold w-100" href="{{ route('admin.users.index') }}">Users</a>
                        </div>
                        <div class="col-6">
                            <a class="btn btn-light rounded-0 fw-bold w-100" href="{{ route('admin.products.index') }}">Products</a>
                        </div>
                        <div class="col-6">
                            <a class="btn btn-light rounded-0 fw-bold w-100" href="{{ route('admin.inventory.index') }}">Thresholds</a>
                        </div>
                        <div class="col-6">
                            <a class="btn btn-light rounded-0 fw-bold w-100" href="{{ route('admin.settings.index') }}">Settings</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>