@php
    $links = [
        ['route' => 'home', 'path' => '/', 'label' => 'Home', 'active' => 'home'],
        ['route' => 'about', 'path' => '/about', 'label' => 'About Us', 'active' => 'about'],
        ['route' => 'services', 'path' => '/services', 'label' => 'Services', 'active' => 'services'],
        ['route' => 'services', 'path' => '/services#order', 'label' => 'Order Now', 'active' => 'services.order'],
    ];
@endphp
<nav class="navbar navbar-expand-lg sticky-top"><div class="container"><a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('home') }}"><img src="{{ asset('assets/logo.png') }}" height="42" alt="AquaTrack logo"><span class="brand-px">AquaTrack</span></a><button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nv" aria-label="Toggle menu"><span class="navbar-toggler-icon"></span></button><div class="collapse navbar-collapse" id="nv"><ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
@foreach ($links as $link)
<li class="nav-item"><a class="nav-link {{ request()->routeIs($link['active']) ? 'active' : '' }}" href="{{ $link['path'] }}">{{ $link['label'] }}</a></li>
@endforeach
@guest
<li class="nav-item"><a class="btn btn-aqua btn-sm px-3" href="{{ route('login') }}">Log In</a></li>
@endguest
@auth
<li class="nav-item dropdown">
    <button class="btn btn-aqua btn-sm px-3 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">{{ auth()->user()->name }}</button>
    <ul class="dropdown-menu dropdown-menu-end">
        <li><span class="dropdown-item-text text-capitalize">{{ auth()->user()->role }}</span></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="{{ route('dashboard') }}">Dashboard</a></li>
        <li>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="dropdown-item">Log Out</button>
            </form>
        </li>
    </ul>
</li>
@endauth
</ul></div></div></nav>
