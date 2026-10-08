<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public const INTERNAL_ROLES = [User::ROLE_ADMIN, User::ROLE_STAFF, User::ROLE_DRIVER];

    public function index(Request $request): View
    {
        $users = User::query()
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->string('role')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(fn ($sub) => $sub
                    ->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term));
            })
            ->orderBy('role')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'internalRoles' => self::INTERNAL_ROLES,
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', ['user' => new User]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules($request));
        $data['role'] = User::resolveRole($data['email']);

        $this->guardInternalDomain($data['role']);

        $data['password'] = Hash::make($data['password']);
        $data['city'] = ($data['city'] ?? null) ?: 'Marikina City';

        User::create($data);

        return redirect()->route('admin.users.index')->with('status', 'User created.');
    }

    public function edit(User $user): RedirectResponse|View
    {
        abort_unless(in_array($user->role, self::INTERNAL_ROLES, true), 403, 'Customer accounts are managed by the customer.');

        return view('admin.users.form', ['user' => $user]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless(in_array($user->role, self::INTERNAL_ROLES, true), 403, 'Customer accounts are managed by the customer.');

        $data = $request->validate($this->rules($request, $user));

        // role always follows the email domain, never the form
        $data['role'] = User::resolveRole($data['email']);

        $this->guardInternalDomain($data['role']);

        if ($user->id === auth()->id() && $data['role'] !== User::ROLE_ADMIN) {
            return back()->withErrors(['email' => 'You cannot remove your own admin role.']);
        }

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('status', 'User updated.');
    }

    // deleting a user would cascade into their orders, so block it
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['delete' => 'You cannot delete your own account.']);
        }

        if ($user->orders()->exists()) {
            return back()->withErrors(['delete' => 'This user has order history and cannot be deleted.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'User deleted.');
    }

    private function rules(Request $request, ?User $user = null): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user?->id)],
            'mobile' => ['nullable', 'string', 'regex:/^09\d{9}$/'],
            'city' => ['nullable', 'string', 'max:100'],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:5', 'confirmed'],
        ];
    }

    private function guardInternalDomain(string $role): void
    {
        if (in_array($role, self::INTERNAL_ROLES, true)) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => 'Internal accounts must use an @admin.com, @staff.com, or @delivery.com email address.',
        ]);
    }
}
