<x-app-layout>
    <x-slot name="title">Settings | AquaTrack</x-slot>

    <div class="container section">
        <h1 class="mb-4">Settings</h1>

        @if (session('status')) <div class="alert alert-success rounded-0">{{ session('status') }}</div> @endif

        <div class="row">
            <div class="col-lg-6">
                <div class="px-card">
                    <h2 class="h5">Ordering</h2>

                    <form method="POST" action="{{ route('admin.settings.update') }}">
                        @csrf
                        @method('PUT')

                        <label class="form-label" for="delivery_fee">Delivery fee (₱)</label>
                        <input id="delivery_fee" class="form-control" type="number" step="0.01" min="0" name="delivery_fee"
                               value="{{ old('delivery_fee', number_format((float) $deliveryFee, 2, '.', '')) }}" required>
                        <div class="form-text">Applied once to delivery orders only. Pickup orders are not charged.</div>
                        @error('delivery_fee') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

                        <button class="btn btn-aqua px-4 mt-3" type="submit">Save settings</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>