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
            <input id="password" class="form-control" type="password" name="password" required autocomplete="current-password">
            @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        <div class="form-check mb-1">
            <input class="form-check-input" type="checkbox" id="remember" name="remember">
            <label class="form-check-label" for="remember">Keep me logged in</label>
        </div>

        <button class="btn btn-aqua w-100" type="submit">Log in</button>
    </form>

    <p class="small mt-3 mb-0 text-center">New customer? <a href="{{ route('register') }}">Create an account</a></p>
</x-guest-layout>
