<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password') + ['is_active' => true];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            // Same message for unknown e-mail, wrong password or disabled account.
            throw ValidationException::withMessages(['email' => 'Identifiants incorrects.']);
        }

        $request->session()->regenerate();

        $user = $request->user();
        $default = $user->can('admin.access') ? route('admin.dashboard') : route('account.dashboard');

        return redirect()->intended($default)
            ->with('toast', ['type' => 'success', 'message' => 'Bon retour parmi nous, '.explode(' ', $user->name)[0].' !']);
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('toast', ['type' => 'success', 'message' => 'Vous êtes déconnecté(e). À bientôt !']);
    }
}
