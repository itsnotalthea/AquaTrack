<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    // public registration is customers only, internal roles are created by admin
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'mobile' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'password' => ['required', 'string', 'min:5', 'confirmed', Rules\Password::defaults()],
            'house_no' => ['required', 'string', 'max:50'],
            'street' => ['required', 'string', 'max:150'],
            'subdivision' => ['nullable', 'string', 'max:150'],
            'city' => ['required', 'string', 'max:100'],
        ], [
            'mobile.regex' => 'Mobile must be 11 digits starting with 09.',
            'password.min' => 'Password must be at least 5 characters.',
        ]);

        $user = User::create($validated + ['role' => User::ROLE_CUSTOMER]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('customer.dashboard'));
    }
}
