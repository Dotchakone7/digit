<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:190', 'unique:users,email'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9\s\-\.]{8,20}$/'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'terms' => ['accepted'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nom complet', 'email' => 'adresse e-mail', 'phone' => 'téléphone', 'password' => 'mot de passe', 'terms' => 'conditions générales'];
    }

    public function messages(): array
    {
        return ['phone.regex' => 'Le numéro de téléphone n’est pas valide.', 'terms.accepted' => 'Veuillez accepter les conditions générales.'];
    }
}
