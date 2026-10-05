<?php

namespace App\Http\Requests\Shop;

use App\Payments\PaymentManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaceOrderRequest extends FormRequest
{
    public function rules(): array
    {
        $newAddress = fn () => ! $this->filled('address_id');

        return [
            'customer_name' => ['required', 'string', 'min:2', 'max:120'],
            'customer_email' => ['required', 'email:rfc', 'max:190'],
            'customer_phone' => ['required', 'string', 'regex:/^\+?[0-9\s\-\.]{8,20}$/'],

            'address_id' => ['nullable', 'integer', Rule::exists('addresses', 'id')->where('user_id', $this->user()->id)],
            'address.full_name' => [Rule::requiredIf($newAddress), 'nullable', 'string', 'max:120'],
            'address.phone' => [Rule::requiredIf($newAddress), 'nullable', 'string', 'regex:/^\+?[0-9\s\-\.]{8,20}$/'],
            'address.city' => [Rule::requiredIf($newAddress), 'nullable', 'string', 'max:120'],
            'address.district' => ['nullable', 'string', 'max:120'],
            'address.street' => [Rule::requiredIf($newAddress), 'nullable', 'string', 'max:255'],
            'address.landmark' => ['nullable', 'string', 'max:255'],
            'save_address' => ['nullable', 'boolean'],

            'shipping_method_id' => ['required', 'integer', Rule::exists('shipping_methods', 'id')->where('is_active', true)],
            'payment_method' => ['required', 'string', Rule::in(app(PaymentManager::class)->available()->keys()->all())],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_name' => 'nom complet', 'customer_email' => 'adresse e-mail', 'customer_phone' => 'téléphone',
            'address.full_name' => 'nom du destinataire', 'address.phone' => 'téléphone du destinataire',
            'address.city' => 'ville', 'address.district' => 'commune / quartier', 'address.street' => 'adresse',
            'address.landmark' => 'point de repère', 'shipping_method_id' => 'mode de livraison',
            'payment_method' => 'moyen de paiement', 'notes' => 'instructions',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_phone.regex' => 'Le numéro de téléphone n’est pas valide.',
            'address.phone.regex' => 'Le numéro du destinataire n’est pas valide.',
        ];
    }
}
