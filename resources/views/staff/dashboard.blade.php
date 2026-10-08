<x-app-layout>
    <x-slot name="title">Staff Dashboard | AquaTrack</x-slot>

    <div class="container section">
        <h1 class="mb-4">Staff Dashboard</h1>

        @if (session('status')) <div class="alert alert-success rounded-0">{{ session('status') }}</div> @endif

        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-3">
                <a class="text-decoration-none" href="{{ route('staff.orders.index', ['status' => 'pending']) }}">
                    <div class="px-card">
                        <div class="ico">📋</div>
                        <h4 class="h6 mt-2">Pending orders</h4>
                        <p class="display-6 mb-0">{{ $pendingOrders }}</p>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="px-card">
                    <div class="ico">🗓️</div>
                    <h4 class="h6 mt-2">Today's orders</h4>
                    <p class="display-6 mb-0">{{ $todaysOrders }}</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="px-card">
                    <div class="ico">💵</div>
                    <h4 class="h6 mt-2">Awaiting payment</h4>
                    <p class="display-6 mb-0">{{ $awaitingPayment }}</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="px-card">
                    <div class="ico">🚚</div>
                    <h4 class="h6 mt-2">Active deliveries</h4>
                    <p class="display-6 mb-0">{{ $activeDeliveries }}</p>
                </div>
            </div>
        </div>

        <div class="px-card mb-4">
            <h2 class="h5 mb-3">Container station fill</h2>
            @include('partials.container-visual')
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="px-card h-100">
                    <h2 class="h5">Low stock
                        <span class="badge text-bg-danger rounded-0" data-low-stock-badge>{{ $lowStock->count() }}</span>
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
                    <a class="btn btn-aqua px-3" href="{{ route('staff.inventory') }}">Inventory</a>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="px-card h-100">
                    <h2 class="h5">Reports</h2>
                    <div class="row g-2 mt-1">
                        @foreach (\App\Http\Controllers\Staff\ReportController::TYPES as $key => $label)
                            <div class="col-6">
                                <a class="btn btn-light rounded-0 fw-bold w-100" href="{{ route('staff.reports.index', ['type' => $key]) }}">{{ $label }}</a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>