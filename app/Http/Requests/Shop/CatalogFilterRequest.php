<?php

namespace App\Http\Requests\Shop;

use App\Services\Catalog\ProductSearch;
use App\Support\Money;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CatalogFilterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'in_stock' => ['nullable', 'boolean'],
            'on_sale' => ['nullable', 'boolean'],
            'sort' => ['nullable', Rule::in(array_keys(ProductSearch::SORTS))],
            'view' => ['nullable', Rule::in(['grid', 'list'])],
        ];
    }

    /** Invalid filters are simply ignored instead of producing an error page. */
    protected function failedValidation(Validator $validator): void
    {
        foreach (array_keys($validator->failed()) as $field) {
            $this->offsetUnset($field);
        }
    }

    public function filters(): array
    {
        return [
            'q' => trim((string) $this->input('q')) ?: null,
            'min_price' => Money::toMinor($this->input('min_price')),
            'max_price' => Money::toMinor($this->input('max_price')),
            'in_stock' => $this->boolean('in_stock'),
            'on_sale' => $this->boolean('on_sale'),
            'sort' => in_array($this->input('sort'), array_keys(ProductSearch::SORTS), true) ? $this->input('sort') : 'popular',
        ];
    }
}
