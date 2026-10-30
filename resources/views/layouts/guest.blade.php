<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light" translate="no">
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
    <body class="has-topnav auth-page">
        @include('partials.flash')

        @include('partials.nav')

        <main class="login-wrap page-enter" id="appMain">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-7 col-lg-5 col-xl-4">
                        <div class="px-card auth-card text-center">
                            <img src="{{ asset('assets/logo.png') }}" height="96" alt="AquaTrack logo" class="mb-3 auth-logo">
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </div>
        </main>

        @include('partials.footer')

        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
        <script src="{{ asset('js/aquatrack.js') }}"></script>
        @stack('scripts')
    </body>
</html>
