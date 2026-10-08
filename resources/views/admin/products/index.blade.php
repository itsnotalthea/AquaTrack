<x-app-layout>
    <x-slot name="title">Products | AquaTrack</x-slot>

    <div class="container section">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <h1 class="mb-0">Products</h1>
            <a class="btn btn-aqua px-3" href="{{ route('admin.products.create') }}">New product</a>
        </div>

        @if (session('status')) <div class="alert alert-success rounded-0">{{ session('status') }}</div> @endif
        @if ($errors->has('delete')) <div class="alert alert-danger rounded-0">{{ $errors->first('delete') }}</div> @endif

        <div class="px-card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Unit</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr>
                                <td>{{ $product->name }}</td>
                                <td>₱{{ number_format((float) $product->price, 2) }}</td>
                                <td class="text-capitalize">{{ $product->unit }}</td>
                                <td>
                                    @if ($product->is_active)
                                        <span class="badge text-bg-success rounded-0">Active</span>
                                    @else
                                        <span class="badge text-bg-secondary rounded-0">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <a class="btn btn-sm btn-aqua px-2" href="{{ route('admin.products.edit', $product) }}">Edit</a>
                                    <form class="d-inline" method="POST" action="{{ route('admin.products.destroy', $product) }}"
                                          onsubmit="return confirm('Delete this product?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger rounded-0 px-2" type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">No products yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="small text-muted mt-3 mb-0">
                Products used in past orders cannot be deleted. Set them to inactive to hide them from new orders.
            </p>
        </div>
    </div>
</x-app-layout>