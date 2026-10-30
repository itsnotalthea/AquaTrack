<x-app-layout>
    <x-slot name="title">Services | AquaTrack</x-slot>

    @php
        $orderUrl = route('register');
    @endphp

    <header class="hero py-5">
        <div class="container">
            <h1>Services</h1>
            <p class="lead mb-0">Refills, new containers, dispenser rentals, and delivery from AquaTrack Water Station.</p>
        </div>
    </header>

    <section class="section">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
                <div>
                    <h2 class="mb-1">Service options</h2>
                    <p class="text-muted mb-0">Current prices and availability are shown when you place an order.</p>
                </div>
                <a class="btn btn-aqua" href="{{ $orderUrl }}">Order water</a>
            </div>

            <div class="product-grid">
                @foreach ([
                    ['icon' => 'droplet', 'name' => 'Water refills', 'description' => 'Refill your reusable 5-gallon containers with purified water.'],
                    ['icon' => 'shopping-bag', 'name' => 'New containers', 'description' => 'Add a new 5-gallon container to your next water order.'],
                    ['icon' => 'calendar', 'name' => 'Dispenser rentals', 'description' => 'Ask about dispenser rentals when you build your order.'],
                ] as $service)
                    <div class="px-card product-card">
                        <x-icon :name="$service['icon']" />
                        <h4 class="h5 mb-1">{{ $service['name'] }}</h4>
                        <p class="text-muted small mb-0">{{ $service['description'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="section alt">
        <div class="container">
            <h2 class="mb-4">How it works</h2>
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="px-card">
                        <x-icon name="users" />
                        <h4 class="h5 mt-2">Create an account</h4>
                        <p class="mb-0">Register with your Marikina barangay so deliveries and reports are accurate.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="px-card">
                        <x-icon name="shopping-cart" />
                        <h4 class="h5 mt-2">Build a multi-item order</h4>
                        <p class="mb-0">Mix refills, new containers, and dispenser rentals in one order.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="px-card">
                        <x-icon name="truck" />
                        <h4 class="h5 mt-2">Pickup or delivery</h4>
                        <p class="mb-0">Choose pickup or delivery at checkout. Any delivery fee is shown before you submit your order.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="px-card d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h2 class="h4 mb-1">Ready to order?</h2>
                    <p class="text-muted mb-0">Sign in to choose refill exchange or bring-your-own per item.</p>
                </div>
                <div class="d-flex gap-2">
                    <a class="btn btn-aqua" href="{{ $orderUrl }}">Start an order</a>
                    @guest
                        <a class="btn btn-light" href="{{ route('login') }}">Log in</a>
                    @endguest
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
