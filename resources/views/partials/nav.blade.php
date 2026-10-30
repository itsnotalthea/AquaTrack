@php
    $links = [
        ['route' => 'home', 'path' => '/', 'label' => 'Home', 'active' => 'home'],
        ['route' => 'about', 'path' => '/about', 'label' => 'About Us', 'active' => 'about'],
        ['route' => 'services', 'path' => '/services', 'label' => 'Services', 'active' => 'services'],
    ];

    $orderUrl = auth()->check() && auth()->user()->isCustomer()
        ? route('customer.orders.create')
        : route('register');
@endphp
<nav class="navbar navbar-expand-lg sticky-top" aria-label="Primary">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('home') }}">
            <img src="{{ asset('assets/logo.png') }}" height="42" alt="AquaTrack logo">
            <span class="brand-px">AquaTrack</span>
        </a>

        <div class="d-flex align-items-center gap-2 order-lg-3">
            <button type="button" class="icon-button theme-toggle" data-theme-toggle aria-label="Toggle dark mode">
                <span class="theme-icon-light"><x-icon name="moon" /></span>
                <span class="theme-icon-dark"><x-icon name="sun" /></span>
            </button>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nv" aria-label="Toggle menu">
                <x-icon name="menu" />
            </button>
        </div>

        <div class="collapse navbar-collapse order-lg-2" id="nv">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                @foreach ($links as $link)
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs($link['active']) ? 'active' : '' }}" href="{{ $link['path'] }}">
                            {{ $link['label'] }}
                        </a>
                    </li>
                @endforeach

                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('customer.orders.*') ? 'active' : '' }}" href="{{ $orderUrl }}">Order water</a>
                </li>

                @guest
                    <li class="nav-item"><a class="btn btn-aqua btn-sm px-3" href="{{ route('login') }}">Log In</a></li>
                @endguest

                @auth
                    @if (auth()->user()->isCustomer())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('customer.orders.index') ? 'active' : '' }}" href="{{ route('customer.orders.index') }}">My orders</a>
                        </li>
                    @endif
                    <li class="nav-item dropdown">
                        <button class="btn btn-aqua btn-sm px-3 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ auth()->user()->name }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text text-capitalize">{{ auth()->user()->role }}</span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('dashboard') }}"><x-icon name="home" /> Dashboard</a></li>
                            <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><x-icon name="user" /> Profile</a></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item"><x-icon name="logout" /> Log Out</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</nav>
