<x-app-layout>
    <x-slot name="title">{{ $user->exists ? 'Edit User' : 'New Internal User' }} | AquaTrack</x-slot>

    <div class="container section">
        <h1 class="mb-4">{{ $user->exists ? 'Edit user' : 'New internal user' }}</h1>

        <div class="row">
            <div class="col-lg-7">
                <div class="px-card">
                    <p class="small text-muted">
                        Role is derived from the email domain and cannot be chosen:
                        <code>@admin.com</code>, <code>@staff.com</code>, or <code>@delivery.com</code>.
                    </p>

                    <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}">
                        @csrf
                        @if ($user->exists) @method('PUT') @endif

                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label" for="first_name">First name</label>
                                <input id="first_name" class="form-control" type="text" name="first_name" value="{{ old('first_name', $user->first_name) }}" required>
                                @error('first_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="last_name">Last name</label>
                                <input id="last_name" class="form-control" type="text" name="last_name" value="{{ old('last_name', $user->last_name) }}" required>
                                @error('last_name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="email">Email</label>
                                <input id="email" class="form-control" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                                @error('email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="mobile">Mobile <span class="text-muted">(optional)</span></label>
                                <input id="mobile" class="form-control" type="tel" name="mobile" value="{{ old('mobile', $user->mobile) }}" placeholder="09XXXXXXXXX">
                                @error('mobile') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="city">City</label>
                                <input id="city" class="form-control" type="text" name="city" value="{{ old('city', $user->city ?: 'Marikina City') }}">
                                @error('city') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="password">
                                    {{ $user->exists ? 'New password' : 'Password' }}
                                    @if ($user->exists) <span class="text-muted">(leave blank to keep)</span> @endif
                                </label>
                                <input id="password" class="form-control" type="password" name="password" minlength="5" autocomplete="new-password" @required(! $user->exists)>
                                <div class="form-text">At least 5 characters.</div>
                                @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="password_confirmation">Confirm password</label>
                                <input id="password_confirmation" class="form-control" type="password" name="password_confirmation" minlength="5" autocomplete="new-password" @required(! $user->exists)>
                                @error('password_confirmation') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button class="btn btn-aqua px-4" type="submit">{{ $user->exists ? 'Save changes' : 'Create user' }}</button>
                            <a class="btn btn-light rounded-0 fw-bold px-4" href="{{ route('admin.users.index') }}">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="px-card">
                    <h2 class="h6">Resolved role</h2>
                    <div class="total-box text-capitalize" id="resolvedRole">
                        {{ $user->exists ? $user->role : 'Enter an email above' }}
                    </div>
                    <p class="small text-muted mt-2 mb-0">
                        Customers cannot be created here. They register themselves at
                        <a href="{{ route('register') }}">/register</a>.
                    </p>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // mirrors User::resolveRole so the admin sees the role before saving
            (function () {
                var email = document.getElementById('email');
                var out = document.getElementById('resolvedRole');
                if (!email || !out) return;
                function resolve(value) {
                    var domain = value.split('@')[1];
                    domain = domain ? domain.toLowerCase() : '';
                    if (domain === 'admin.com') return 'admin';
                    if (domain === 'staff.com') return 'staff';
                    if (domain === 'delivery.com') return 'driver';
                    return 'customer';
                }
                email.addEventListener('input', function () {
                    out.textContent = email.value.indexOf('@') > -1 ? resolve(email.value) : 'Enter an email above';
                });
            })();
        </script>
    @endpush
</x-app-layout>