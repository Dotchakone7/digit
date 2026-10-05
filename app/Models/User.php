<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\RoleSlug;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * role_id and is_active are intentionally NOT fillable: they are only
 * changed through dedicated, authorized admin actions.
 */
#[Fillable(['name', 'email', 'phone', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function defaultAddress(): HasOne
    {
        return $this->hasOne(Address::class)->where('is_default', true);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function wishlist(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'wishlist_items')->withTimestamps();
    }

    public function roleSlug(): RoleSlug
    {
        return $this->role?->slug ?? RoleSlug::Customer;
    }

    public function hasRole(RoleSlug ...$roles): bool
    {
        return in_array($this->roleSlug(), $roles, true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(RoleSlug::SuperAdmin);
    }

    public function isStaff(): bool
    {
        return $this->roleSlug()->isStaff();
    }

    /** @return list<string> */
    public function abilities(): array
    {
        return config('permissions.roles.'.$this->roleSlug()->value, []);
    }

    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)))
            ->filter()->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }

    public function hasPurchased(Product $product): bool
    {
        return $this->orders()
            ->where('status', OrderStatus::Delivered)
            ->whereHas('items', fn (Builder $q) => $q->where('product_id', $product->id))
            ->exists();
    }

    public function scopeCustomers(Builder $query): void
    {
        $query->whereHas('role', fn (Builder $q) => $q->where('slug', RoleSlug::Customer));
    }

    public function scopeStaff(Builder $query): void
    {
        $query->whereHas('role', fn (Builder $q) => $q->where('slug', '!=', RoleSlug::Customer));
    }
}
