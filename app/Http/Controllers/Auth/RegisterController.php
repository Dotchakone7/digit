<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RoleSlug;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = new User($request->safe()->only(['name', 'email', 'phone', 'password']));
        $user->email = mb_strtolower($user->email);
        // The role is always forced server-side: registration can never create staff accounts.
        $user->role_id = Role::idFor(RoleSlug::Customer);
        $user->is_active = true;
        $user->save();

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('account.dashboard'))
            ->with('toast', ['type' => 'success', 'message' => 'Bienvenue '.explode(' ', $user->name)[0].' ! Votre compte est créé.']);
    }
}
