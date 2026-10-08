<x-guest-layout>
    <x-slot name="title">Register | AquaTrack</x-slot>

    <h1 class="h4">Create your account</h1>
    <p class="small text-center mb-3">New customers only. Staff and driver accounts are created by the administrator.</p>

    <div id="registerMsg" class="alert rounded-0 d-none"></div>

    <form method="POST" action="{{ route('register') }}" class="text-start needs-validation" novalidate>
        @csrf

        <div class="row g-2">
            <div class="col-md-6">
                <label class="form-label" for="first_name">First name</label>
                <input id="first_name" class="form-control" type="text" name="first_name" value="{{ old('first_name') }}" required autofocus>
                @error('first_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="last_name">Last name</label>
                <input id="last_name" class="form-control" type="text" name="last_name" value="{{ old('last_name') }}" required>
                @error('last_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
                <label class="form-label" for="email">Email</label>
                <input id="email" class="form-control" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
                @error('email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
                <label class="form-label" for="mobile">Mobile number</label>
                <input id="mobile" class="form-control" type="tel" name="mobile" value="{{ old('mobile') }}" placeholder="09XXXXXXXXX" pattern="09[0-9]{9}" required>
                @error('mobile') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="house_no">House no.</label>
                <input id="house_no" class="form-control" type="text" name="house_no" value="{{ old('house_no') }}" required>
                @error('house_no') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="street">Street</label>
                <input id="street" class="form-control" type="text" name="street" value="{{ old('street') }}" required>
                @error('street') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
                <label class="form-label" for="subdivision">Subdivision <span class="text-muted">(optional)</span></label>
                <input id="subdivision" class="form-control" type="text" name="subdivision" value="{{ old('subdivision') }}">
                @error('subdivision') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
                <label class="form-label" for="city">City</label>
                <input id="city" class="form-control" type="text" name="city" value="{{ old('city', 'Marikina City') }}" required>
                @error('city') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
                <label class="form-label" for="password">Password</label>
                <input id="password" class="form-control" type="password" name="password" minlength="5" required autocomplete="new-password">
                <div class="form-text">At least 5 characters.</div>
                @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
                <label class="form-label" for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" class="form-control" type="password" name="password_confirmation" minlength="5" required autocomplete="new-password">
                @error('password_confirmation') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
        </div>

        <button class="btn btn-aqua w-100 mt-3" type="submit">Register</button>
    </form>

    <p class="small mt-3 mb-0 text-center">Already have an account? <a href="{{ route('login') }}">Log in</a></p>
</x-guest-layout>
