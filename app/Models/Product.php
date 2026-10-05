<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Enums\ReviewStatus;
use App\Support\Media;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'category_id', 'name', 'slug', 'sku', 'short_description', 'description', 'price', 'sale_price',
    'sale_starts_at', 'sale_ends_at', 'stock', 'low_stock_threshold', 'status', 'is_featured',
    'specifications', 'meta_title', 'meta_description', 'published_at',
])]
class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'sale_price' => 'integer',
            'stock' => 'integer',
            'low_stock_threshold' => 'integer',
            'sale_starts_at' => 'datetime',
            'sale_ends_at' => 'datetime',
            'published_at' => 'datetime',
            'status' => ProductStatus::class,
            'is_featured' => 'boolean',
            'specifications' => 'array',
            'rating_avg' => 'float',
        ];
    }

    protected static function booted(): void
    {
        $flush = fn () => \Illuminate\Support\Facades\Cache::deleteMultiple(['catalog.vocabulary', 'seo.sitemap']);
        static::saved($flush);
        static::deleted($flush);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderByDesc('is_primary')->orderBy('position');
    }

    public function primaryImage(): HasOne
    {
        // Invariant kept by ProductImageController: a product with images has exactly one primary image.
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position');
    }

    public function activeVariants(): HasMany
    {
        return $this->variants()->where('is_active', true);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('status', ReviewStatus::Approved)->latest();
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // ---- Scopes -----------------------------------------------------------

    public function scopePublished(Builder $query): void
    {
        $query->where('status', ProductStatus::Published)
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function scopeOnSale(Builder $query): void
    {
        $query->whereNotNull('sale_price')
            ->whereColumn('sale_price', '<', 'price')
            ->where(fn (Builder $q) => $q->whereNull('sale_starts_at')->orWhere('sale_starts_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('sale_ends_at')->orWhere('sale_ends_at', '>=', now()));
    }

    public function scopeInStock(Builder $query): void
    {
        $query->where('stock', '>', 0);
    }

    public function scopeLowStock(Builder $query): void
    {
        $default = (int) config('shop.catalog.low_stock_threshold');
        $query->whereRaw('stock <= COALESCE(low_stock_threshold, ?)', [$default]);
    }

    /** Eager loads needed by every product card (avoids N+1 queries). */
    public function scopeForCard(Builder $query): void
    {
        $query->with(['primaryImage', 'category:id,name,slug'])->withCount('activeVariants');
    }

    // ---- Pricing & stock ------------------------------------------------------

    public function isOnSale(): bool
    {
        return $this->sale_price !== null
            && $this->sale_price < $this->price
            && ($this->sale_starts_at === null || $this->sale_starts_at->isPast())
            && ($this->sale_ends_at === null || $this->sale_ends_at->isFuture());
    }

    public function currentPrice(): int
    {
        return $this->isOnSale() ? $this->sale_price : $this->price;
    }

    public function discountPercent(): int
    {
        return $this->isOnSale() ? (int) round(100 - ($this->sale_price * 100 / $this->price)) : 0;
    }

    public function isPublished(): bool
    {
        return $this->status === ProductStatus::Published
            && ($this->published_at === null || $this->published_at->isPast());
    }

    public function hasVariants(): bool
    {
        if (array_key_exists('active_variants_count', $this->attributes)) {
            return $this->attributes['active_variants_count'] > 0;
        }

        return $this->activeVariants->isNotEmpty();
    }

    public function isInStock(): bool
    {
        return $this->stock > 0;
    }

    public function lowStockThreshold(): int
    {
        return $this->low_stock_threshold ?? (int) config('shop.catalog.low_stock_threshold');
    }

    public function isLowStock(): bool
    {
        return $this->stock > 0 && $this->stock <= $this->lowStockThreshold();
    }

    public function isNew(): bool
    {
        $since = $this->published_at ?? $this->created_at;

        return $since !== null && $since->gt(now()->subDays((int) config('shop.catalog.new_product_days')));
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () => Media::url($this->primaryImage?->path));
    }

    /** Recalculates the denormalized rating columns from approved reviews. */
    public function refreshRating(): void
    {
        $stats = $this->approvedReviews()->reorder()
            ->selectRaw('COUNT(*) as total, COALESCE(AVG(rating), 0) as average')
            ->first();

        $this->forceFill([
            'reviews_count' => (int) $stats->total,
            'rating_avg' => round((float) $stats->average, 2),
        ])->saveQuietly();
    }
}
