<?php

namespace App\Http\Requests\Shop;

use App\Models\Review;
use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [Review::class, $this->route('product')]);
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:120'],
            'comment' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return ['rating' => 'note', 'title' => 'titre', 'comment' => 'commentaire'];
    }
}
