<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:60'],
            'full_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9\s\-\.]{8,20}$/'],
            'city' => ['required', 'string', 'max:120'],
            'district' => ['nullable', 'string', 'max:120'],
            'street' => ['required', 'string', 'max:255'],
            'landmark' => ['nullable', 'string', 'max:255'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return ['phone.regex' => 'Le numéro de téléphone n’est pas valide.'];
    }
}
