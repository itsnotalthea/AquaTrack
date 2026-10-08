<x-app-layout>
    <x-slot name="title">Containers | AquaTrack</x-slot>

    <div class="container section">
        <h1 class="mb-1">Container status</h1>
        <p class="text-muted mb-4">Containers are counted by status, not tracked individually.</p>

        <div class="px-card mb-4">
            @include('partials.container-visual')
        </div>

        <div class="row g-4">
            @foreach ($ordered as $container)
                <div class="col-md-6 col-lg-4">
                    <div class="px-card">
                        <h2 class="h6 text-capitalize">{{ str_replace('_', ' ', $container->status) }}</h2>
                        <p class="display-5 mb-0">{{ $container->quantity }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>