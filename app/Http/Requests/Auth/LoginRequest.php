<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:190'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return ['email' => 'adresse e-mail', 'password' => 'mot de passe'];
    }
}
