<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('account.password');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers()],
        ], ['password.different' => 'Le nouveau mot de passe doit être différent de l’actuel.']);

        $request->user()->forceFill(['password' => $data['password']])->save();

        // Signs out every other device using the old password.
        Auth::logoutOtherDevices($data['password']);
        $request->session()->regenerate();

        return back()->with('toast', ['type' => 'success', 'message' => 'Mot de passe modifié. Vos autres sessions ont été déconnectées.']);
    }
}
