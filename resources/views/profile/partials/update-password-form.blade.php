<section class="px-card">
    <h2 class="h5">Update Password</h2>
    <p class="small text-muted">Use at least 5 characters.</p>

    <form method="post" action="{{ route('password.update') }}">
        @csrf
        @method('put')

        <div class="mb-2">
            <label class="form-label" for="update_password_current_password">Current password</label>
            <input id="update_password_current_password" class="form-control" type="password" name="current_password" autocomplete="current-password">
            @error('current_password', 'updatePassword') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        <div class="mb-2">
            <label class="form-label" for="update_password_password">New password</label>
            <input id="update_password_password" class="form-control" type="password" name="password" minlength="5" autocomplete="new-password">
            @error('password', 'updatePassword') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="update_password_password_confirmation">Confirm new password</label>
            <input id="update_password_password_confirmation" class="form-control" type="password" name="password_confirmation" minlength="5" autocomplete="new-password">
            @error('password_confirmation', 'updatePassword') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>

        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-aqua px-4" type="submit">Save</button>
            @if (session('status') === 'password-updated')
                <span class="small text-success">Saved.</span>
            @endif
        </div>
    </form>
</section>
