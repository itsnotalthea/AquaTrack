<x-guest-layout>
    <x-slot name="title">Log In | AquaTrack</x-slot>

    <h1 class="h4">Log in</h1>

    <div id="loginMsg" class="alert rounded-0 d-none"></div>

    @if (session('status'))
        <div class="alert alert-success rounded-0">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="text-start needs-validation" novalidate>
        @csrf

        <div class="mb-3">
            <label class="form-label" for="email">Email</label>
            <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            @error('email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        <div class="mb-2">
            <label class="form-label" for="password">Password</label>
            <div class="input-group">
                <input id="password" class="form-control" type="password" name="password" required autocomplete="current-password">
                <button type="button" class="btn btn-light border" data-password-toggle="password" aria-label="Show password">Show</button>
            </div>
            @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        <div class="form-check mb-1">
            <input class="form-check-input" type="checkbox" id="remember" name="remember">
            <label class="form-check-label" for="remember">Keep me logged in</label>
        </div>

        <button class="btn btn-aqua w-100 snake-button" type="submit"><span class="snake-border" aria-hidden="true"></span>Log in</button>
    </form>

    <p class="small mt-3 mb-0 text-center">New customer? <a href="{{ route('register') }}">Create an account</a></p>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
                    var input = document.getElementById(button.dataset.passwordToggle);
                    if (!input) return;

                    button.addEventListener('click', function () {
                        var showing = input.type === 'text';
                        input.type = showing ? 'password' : 'text';
                        button.textContent = showing ? 'Show' : 'Hide';
                        button.setAttribute('aria-pressed', String(!showing));
                    });
                });
            });
        </script>
    @endpush
</x-guest-layout>
