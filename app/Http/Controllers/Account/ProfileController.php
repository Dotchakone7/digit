<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.profile', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9\s\-\.]{8,20}$/'],
        ], ['phone.regex' => 'Le numéro de téléphone n’est pas valide.']);

        $user->fill($data + ['email' => mb_strtolower($data['email'])])->save();

        return back()->with('toast', ['type' => 'success', 'message' => 'Profil mis à jour.']);
    }
}
