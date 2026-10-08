<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'AquaTrack'))</title>

        <link rel="icon" href="{{ asset('assets/logo.png') }}">

        <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Silkscreen:wght@400;700&family=Nunito+Sans:wght@400;600;700&display=swap" rel="stylesheet">
        <link href="{{ asset('css/style.css') }}" rel="stylesheet">
    </head>
    <body>
        @include('partials.nav')

        <main class="login-wrap">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-6 col-lg-4">
                        <div class="px-card text-center">
                            <img src="{{ asset('assets/logo.png') }}" height="110" alt="AquaTrack logo" class="mb-2">
                            {{ $slot }}
                        </div>
                    </div>
                </div>
            </div>
        </main>

        @include('partials.footer')

        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
        <script src="{{ asset('js/main.js') }}"></script>
        <script src="{{ asset('js/aquatrack.js') }}"></script>
        @stack('scripts')
    </body>
</html>
