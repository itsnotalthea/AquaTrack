<section class="px-card">
    <h2 class="h5">Profile Information</h2>
    <p class="small text-muted">Update your account details and email address.</p>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('patch')

        <div class="row g-2">
            <div class="col-md-6">
                <label class="form-label" for="first_name">First name</label>
                <input id="first_name" class="form-control" type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" required autocomplete="given-name">
                @error('first_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="last_name">Last name</label>
                <input id="last_name" class="form-control" type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" required autocomplete="family-name">
                @error('last_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
                <label class="form-label" for="email">Email</label>
                <input id="email" class="form-control" type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="username">
                @error('email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                    <div class="form-text">
                        Your email address is unverified.
                        <button form="send-verification" class="btn btn-link btn-sm p-0 align-baseline">Re-send the verification email.</button>
                    </div>
                @endif
            </div>
            <div class="col-12">
                <label class="form-label" for="mobile">Mobile number</label>
                <input id="mobile" class="form-control" type="tel" name="mobile" value="{{ old('mobile', $user->mobile) }}" placeholder="09XXXXXXXXX">
                @error('mobile') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label" for="house_no">House no.</label>
                <input id="house_no" class="form-control" type="text" name="house_no" value="{{ old('house_no', $user->house_no) }}">
                @error('house_no') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-8">
                <label class="form-label" for="street">Street</label>
                <input id="street" class="form-control" type="text" name="street" value="{{ old('street', $user->street) }}">
                @error('street') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="subdivision">Subdivision</label>
                <input id="subdivision" class="form-control" type="text" name="subdivision" value="{{ old('subdivision', $user->subdivision) }}">
                @error('subdivision') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6">
                <label class="form-label" for="city">City</label>
                <input id="city" class="form-control" type="text" name="city" value="{{ old('city', $user->city) }}">
                @error('city') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="d-flex align-items-center gap-3 mt-3">
            <button class="btn btn-aqua px-4" type="submit">Save</button>
            @if (session('status') === 'profile-updated')
                <span class="small text-success">Saved.</span>
            @endif
        </div>
    </form>
</section>
