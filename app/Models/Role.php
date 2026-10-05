<?php

namespace App\Models;

use App\Enums\RoleSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name', 'description'])]
class Role extends Model
{
    protected function casts(): array
    {
        return ['slug' => RoleSlug::class];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public static function idFor(RoleSlug $slug): int
    {
        return once(fn () => static::query()->pluck('id', 'slug')->all())[$slug->value]
            ?? static::query()->where('slug', $slug)->valueOrFail('id');
    }
}
