<x-app-layout>
    <x-slot name="title">{{ $product->exists ? 'Edit Product' : 'New Product' }} | AquaTrack</x-slot>

    <div class="container section">
        <h1 class="mb-4">{{ $product->exists ? 'Edit product' : 'New product' }}</h1>

        <div class="row">
            <div class="col-lg-7">
                <div class="px-card">
                    <form method="POST" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}">
                        @csrf
                        @if ($product->exists) @method('PUT') @endif

                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label" for="name">Name</label>
                                <input id="name" class="form-control" type="text" name="name" value="{{ old('name', $product->name) }}" required>
                                @error('name') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="description">Description <span class="text-muted">(optional)</span></label>
                                <textarea id="description" class="form-control" name="description" rows="3">{{ old('description', $product->description) }}</textarea>
                                @error('description') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="price">Price (₱)</label>
                                <input id="price" class="form-control" type="number" step="0.01" min="0" name="price"
                                       value="{{ old('price', $product->exists ? number_format((float) $product->price, 2, '.', '') : '') }}" required>
                                @error('price') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="unit">Unit</label>
                                <select id="unit" class="form-select" name="unit" required>
                                    <option value="gallon" @selected(old('unit', $product->unit) === 'gallon')>Gallon</option>
                                    <option value="piece" @selected(old('unit', $product->unit) === 'piece')>Piece</option>
                                    <option value="month" @selected(old('unit', $product->unit) === 'month')>Month</option>
                                </select>
                                @error('unit') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                                           @checked(old('is_active', $product->exists ? $product->is_active : true))>
                                    <label class="form-check-label" for="is_active">Active (available for new orders)</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button class="btn btn-aqua px-4" type="submit">{{ $product->exists ? 'Save changes' : 'Create product' }}</button>
                            <a class="btn btn-light rounded-0 fw-bold px-4" href="{{ route('admin.products.index') }}">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>