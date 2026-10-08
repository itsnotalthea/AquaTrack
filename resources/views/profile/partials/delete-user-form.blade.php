<section class="px-card">
    <h2 class="h5">Delete Account</h2>
    <p class="small text-muted">This permanently removes your account and its data.</p>

    <button class="btn btn-danger rounded-0 px-4" type="button" data-bs-toggle="collapse" data-bs-target="#deleteAccountForm" aria-expanded="false" aria-controls="deleteAccountForm">
        Delete Account
    </button>

    <div class="collapse mt-3" id="deleteAccountForm">
        <form method="post" action="{{ route('profile.destroy') }}">
            @csrf
            @method('delete')

            <label class="form-label" for="delete_password">Confirm with your password</label>
            <input id="delete_password" class="form-control" type="password" name="password" placeholder="Password">
            @error('password', 'userDeletion') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

            <button class="btn btn-danger rounded-0 px-4 mt-3" type="submit">Permanently delete</button>
        </form>
    </div>
</section>
