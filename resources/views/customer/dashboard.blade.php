<x-app-layout>
    <x-slot name="title">My Dashboard | AquaTrack</x-slot>

    <div class="container section">
        <h1 class="mb-4">Welcome, {{ auth()->user()->name }}</h1>
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <a class="text-decoration-none" href="{{ route('customer.orders.create') }}">
                    <div class="px-card">
                        <div class="ico">🛒</div>
                        <h4 class="h6 mt-2">Place an order</h4>
                        <p class="mb-0">Refills, new containers, or dispenser rental.</p>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-lg-4">
                <a class="text-decoration-none" href="{{ route('customer.orders.index') }}">
                    <div class="px-card">
                        <div class="ico">📋</div>
                        <h4 class="h6 mt-2">Order history</h4>
                        <p class="mb-0">Track the status of your orders.</p>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-lg-4">
                <a class="text-decoration-none" href="{{ route('profile.edit') }}">
                    <div class="px-card">
                        <div class="ico">👤</div>
                        <h4 class="h6 mt-2">Profile</h4>
                        <p class="mb-0">Update your details and delivery address.</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
