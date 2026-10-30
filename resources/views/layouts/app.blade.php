<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" translate="no">
    @php
        $role = auth()->user()?->role;
        $isMarketingPage = ! auth()->check();
        $isInternalScreen = ! $isMarketingPage && in_array($role, [
            \App\Models\User::ROLE_ADMIN,
            \App\Models\User::ROLE_STAFF,
            \App\Models\User::ROLE_DRIVER,
        ], true);
        $isCustomerScreen = ! $isMarketingPage && $role === \App\Models\User::ROLE_CUSTOMER;
    @endphp
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'AquaTrack') }}</title>

        <script>
            (function () {
                try {
                    var saved = localStorage.getItem('aquatrack-theme');
                    document.documentElement.dataset.theme = saved === 'dark' ? 'dark' : 'light';
                } catch (e) {
                    document.documentElement.dataset.theme = 'light';
                }
            })();
        </script>

        <link rel="icon" href="{{ asset('assets/logo.png') }}">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Silkscreen:wght@400;700&family=Nunito+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
        <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    </head>
    <body class="{{ $isInternalScreen ? 'has-sidebar' : ($isCustomerScreen ? 'has-customer-nav' : 'has-topnav') }}">
        <!-- Splash Screen -->
        <div id="splashScreen" class="splash-screen" aria-hidden="true">
            <div class="splash-content">
                <span class="splash-text" id="splashText"></span>
            </div>
        </div>

        @include('partials.flash')

        @if ($isInternalScreen)
            <div class="app-shell">
                @include('partials.sidebar')

                <div class="app-stage">
                    <header class="app-topbar">
                        <button type="button" class="icon-button" data-sidebar-toggle aria-label="Toggle navigation" aria-controls="appSidebar" aria-expanded="true">
                            <x-icon name="menu" />
                        </button>

                        <span class="topbar-crumb">
                            <x-icon name="droplet" />
                            AquaTrack operations
                        </span>

                        <div class="topbar-actions">
                            <button type="button" class="icon-button theme-toggle" data-theme-toggle aria-label="Toggle dark mode">
                                <span class="theme-icon-light"><x-icon name="moon" /></span>
                                <span class="theme-icon-dark"><x-icon name="sun" /></span>
                            </button>
                            <span class="topbar-user">{{ auth()->user()->name }}</span>
                        </div>
                    </header>

                    <main class="app-main page-enter" id="appMain">
                        {{ $slot }}
                    </main>
                </div>
            </div>
        @elseif ($isCustomerScreen)
            @include('partials.customer-nav')

            <main class="app-main page-enter" id="appMain">
                {{ $slot }}
            </main>
        @else
            @include('partials.nav')

            <main class="app-main page-enter" id="appMain">
                {{ $slot }}
            </main>

            @include('partials.footer')
        @endif

        @stack('modals')

        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
        <script src="{{ asset('js/aquatrack.js') }}"></script>
        @stack('scripts')
    </body>
</html>
