<x-app-layout>
    <x-slot name="title">Users | AquaTrack</x-slot>

    <div class="container section">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <h1 class="mb-0">Users</h1>
            <a class="btn btn-aqua px-3" href="{{ route('admin.users.create') }}">New internal user</a>
        </div>

        @if (session('status')) <div class="alert alert-success rounded-0">{{ session('status') }}</div> @endif
        @if ($errors->has('delete')) <div class="alert alert-danger rounded-0">{{ $errors->first('delete') }}</div> @endif

        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-4">
                <input class="form-control" type="search" name="q" value="{{ request('q') }}" placeholder="Search name or email">
            </div>
            <div class="col-md-3">
                <select class="form-select" name="role">
                    <option value="">All roles</option>
                    <option value="admin" @selected(request('role') === 'admin')>Admin</option>
                    <option value="staff" @selected(request('role') === 'staff')>Staff</option>
                    <option value="driver" @selected(request('role') === 'driver')>Driver</option>
                    <option value="customer" @selected(request('role') === 'customer')>Customer</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-aqua w-100" type="submit">Filter</button>
            </div>
        </form>

        <div class="px-card">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td class="text-capitalize">{{ $user->role }}</td>
                                <td>
                                    @if (in_array($user->role, $internalRoles, true))
                                        <a class="btn btn-sm btn-aqua px-2" href="{{ route('admin.users.edit', $user) }}">Edit</a>
                                        <form class="d-inline" method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                              onsubmit="return confirm('Delete this user?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger rounded-0 px-2" type="submit">Delete</button>
                                        </form>
                                    @else
                                        <span class="small text-muted">Self-managed</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">No users found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $users->links() }}</div>
        </div>
    </div>
</x-app-layout>