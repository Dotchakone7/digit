<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product ? $this->user()->can('update', $product) : $this->user()->can('create', Product::class);
    }

    /** Amounts are typed in major units ("12 500") and stored in minor units. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->input('slug') ?: $this->input('name', '')),
            'sku' => Str::upper(trim((string) $this->input('sku'))),
            'price' => Money::toMinor($this->input('price')),
            'sale_price' => Money::toMinor($this->input('sale_price')),
            'specifications' => collect($this->input('specifications', []))
                ->filter(fn ($row) => filled($row['label'] ?? null) && filled($row['value'] ?? null))->values()->all(),
            'variants' => collect($this->input('variants', []))
                ->filter(fn ($row) => filled($row['name'] ?? null))
                ->map(fn ($row) => array_merge($row, ['sku' => Str::upper(trim((string) ($row['sku'] ?? ''))), 'price' => Money::toMinor($row['price'] ?? null)]))
                ->values()->all(),
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('product')?->id;
        $maxKb = (int) config('shop.uploads.max_kb');

        return [
            'name' => ['required', 'string', 'max:180'],
            'slug' => ['required', 'string', 'max:200', 'alpha_dash', Rule::unique('products', 'slug')->ignore($id)],
            'sku' => ['required', 'string', 'max:64', 'regex:/^[A-Z0-9\-_.]+$/', Rule::unique('products', 'sku')->ignore($id)],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:20000'],
            'price' => ['required', 'integer', 'min:0', 'max:999999999'],
            'sale_price' => ['nullable', 'integer', 'min:0', 'lt:price'],
            'sale_starts_at' => ['nullable', 'date'],
            'sale_ends_at' => ['nullable', 'date', 'after_or_equal:sale_starts_at'],
            'stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'is_featured' => ['nullable', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'specifications' => ['array', 'max:30'],
            'specifications.*.label' => ['required', 'string', 'max:80'],
            'specifications.*.value' => ['required', 'string', 'max:255'],
            'variants' => ['array', 'max:60'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.name' => ['required', 'string', 'max:120'],
            'variants.*.sku' => ['required', 'string', 'max:64', 'distinct', 'regex:/^[A-Z0-9\-_.]+$/'],
            'variants.*.price' => ['nullable', 'integer', 'min:0'],
            'variants.*.stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'variants.*.is_active' => ['nullable', 'boolean'],
            // Strict upload validation: real image MIME (content-sniffed), size and dimensions.
            'images' => ['array', 'max:10'],
            'images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', "max:{$maxKb}", 'dimensions:min_width=200,min_height=200,max_width=8000,max_height=8000'],
        ];
    }

    public function messages(): array
    {
        return [
            'sale_price.lt' => 'Le prix promotionnel doit être inférieur au prix normal.',
            'sku.regex' => 'La référence ne peut contenir que des lettres, chiffres, tirets, points et underscores.',
            'variants.*.sku.distinct' => 'Chaque variante doit avoir une référence unique.',
            'images.*.dimensions' => 'Chaque image doit mesurer entre 200 et 8000 pixels de côté.',
        ];
    }

    public function attributes(): array
    {
        return ['variants.*.name' => 'nom de variante', 'variants.*.sku' => 'référence de variante', 'variants.*.stock' => 'stock de variante',
            'specifications.*.label' => 'caractéristique', 'specifications.*.value' => 'valeur', 'low_stock_threshold' => 'seuil de stock faible'];
    }
}
