@php
    $role = auth()->user()?->role;
    $groups = [];

    if ($role === \App\Models\User::ROLE_ADMIN) {
        $groups = [
            'Operations' => [
                ['route' => 'operations.dashboard', 'icon' => 'home', 'label' => 'Dashboard'],
                ['route' => 'operations.orders.index', 'icon' => 'shopping-cart', 'label' => 'Orders'],
                ['route' => 'operations.inventory', 'icon' => 'package', 'label' => 'Inventory'],
                ['route' => 'operations.containers', 'icon' => 'droplet', 'label' => 'Containers'],
                ['route' => 'operations.rentals.index', 'icon' => 'calendar', 'label' => 'Dispenser rentals'],
                ['route' => 'operations.reports.index', 'icon' => 'chart-bar-big', 'label' => 'Reports', 'params' => ['type' => 'sales']],
            ],
            'Administration' => [
                ['route' => 'admin.users.index', 'icon' => 'users', 'label' => 'Users'],
                ['route' => 'admin.products.index', 'icon' => 'shopping-bag', 'label' => 'Products'],
                ['route' => 'admin.inventory.index', 'icon' => 'archive', 'label' => 'Stock rules'],
                ['route' => 'admin.settings.index', 'icon' => 'settings-2', 'label' => 'Settings'],
            ],
        ];
    } elseif ($role === \App\Models\User::ROLE_STAFF) {
        $groups = [
            'Operations' => [
                ['route' => 'operations.dashboard', 'icon' => 'home', 'label' => 'Dashboard'],
                ['route' => 'operations.orders.index', 'icon' => 'shopping-cart', 'label' => 'Orders'],
                ['route' => 'operations.inventory', 'icon' => 'package', 'label' => 'Inventory'],
                ['route' => 'operations.containers', 'icon' => 'droplet', 'label' => 'Containers'],
                ['route' => 'operations.rentals.index', 'icon' => 'calendar', 'label' => 'Dispenser rentals'],
                ['route' => 'operations.reports.index', 'icon' => 'chart-bar-big', 'label' => 'Reports', 'params' => ['type' => 'sales']],
            ],
        ];
    } elseif ($role === \App\Models\User::ROLE_DRIVER) {
        $groups = [
            'Delivery' => [
                ['route' => 'driver.dashboard', 'icon' => 'home', 'label' => 'Dashboard'],
                ['route' => 'driver.deliveries.index', 'icon' => 'truck', 'label' => 'My deliveries'],
            ],
        ];
    }
@endphp

<aside class="app-sidebar" id="appSidebar" aria-label="Main navigation">
    <a class="sidebar-brand" href="{{ route('dashboard') }}">
        <img src="{{ asset('assets/logo.png') }}" height="38" alt="">
        <span class="brand-px">AquaTrack</span>
    </a>

    <nav class="sidebar-nav">
        @foreach ($groups as $heading => $links)
            <div class="sidebar-heading">{{ $heading }}</div>
            @foreach ($links as $link)
                @php
                    $isActive = request()->routeIs($link['route'])
                        || request()->routeIs(str_replace('.index', '.*', $link['route']));
                @endphp
                <a class="sidebar-link {{ $isActive ? 'active' : '' }}"
                   href="{{ route($link['route'], $link['params'] ?? []) }}">
                    <x-icon :name="$link['icon']" />
                    <span>{{ $link['label'] }}</span>
                </a>
            @endforeach
        @endforeach
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <span class="avatar-px">{{ strtoupper(substr(auth()->user()->first_name ?? 'A', 0, 1)) }}</span>
            <span>
                <strong>{{ auth()->user()->name }}</strong>
                <small class="text-capitalize">{{ auth()->user()->role }}</small>
            </span>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="sidebar-link sidebar-logout">
                <x-icon name="logout" />
                <span>Log out</span>
            </button>
        </form>
    </div>
</aside>
