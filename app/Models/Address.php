<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['label', 'full_name', 'phone', 'city', 'district', 'street', 'landmark', 'is_default'])]
class Address extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Immutable copy stored on the order. */
    public function toSnapshot(): array
    {
        return $this->only(['full_name', 'phone', 'city', 'district', 'street', 'landmark']);
    }

    public function oneLine(): string
    {
        return collect([$this->street, $this->district, $this->city])->filter()->implode(', ');
    }
}
