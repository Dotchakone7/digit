<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddToCartRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->whereNull('deleted_at')],
            'variant_id' => ['nullable', 'integer', Rule::exists('product_variants', 'id')->where('product_id', $this->integer('product_id'))],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.config('shop.catalog.max_quantity_per_line')],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.exists' => "Ce produit n'est plus disponible.",
            'variant_id.exists' => "Cette option n'est pas disponible.",
            'quantity.max' => 'Quantité maximale par article : :max.',
        ];
    }
}
