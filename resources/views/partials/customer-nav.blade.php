<header class="customer-topbar">
    <a class="customer-brand" href="{{ route('customer.dashboard') }}">
        <img src="{{ asset('assets/logo.png') }}" height="38" alt="">
        <span>AquaTrack</span>
    </a>

    <nav class="customer-nav" aria-label="Customer navigation">
        <a class="{{ request()->routeIs('customer.dashboard') ? 'active' : '' }}" href="{{ route('customer.dashboard') }}">Dashboard</a>
        <a class="{{ request()->routeIs('customer.orders.create') ? 'active' : '' }}" href="{{ route('customer.orders.create') }}">Place an order</a>
        <a class="{{ request()->routeIs('customer.orders.index', 'customer.orders.show') ? 'active' : '' }}" href="{{ route('customer.orders.index') }}">My orders</a>
        <a class="{{ request()->routeIs('profile.edit') ? 'active' : '' }}" href="{{ route('profile.edit') }}">Profile</a>
    </nav>

    <div class="customer-actions">
        <button type="button" class="icon-button theme-toggle" data-theme-toggle aria-label="Toggle dark mode">
            <span class="theme-icon-light"><x-icon name="moon" /></span>
            <span class="theme-icon-dark"><x-icon name="sun" /></span>
        </button>
        <span class="customer-user">{{ auth()->user()->name }}</span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="customer-logout"><x-icon name="logout" /> Log out</button>
        </form>
    </div>
</header>