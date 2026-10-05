<?php

namespace App\Services\Catalog;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Catalog filtering + tolerant text search: accent/case-insensitive,
 * multi-word, and "did you mean" suggestions for typos.
 */
class ProductSearch
{
    public const SORTS = [
        'popular' => 'Popularité',
        'newest' => 'Nouveautés',
        'price_asc' => 'Prix croissant',
        'price_desc' => 'Prix décroissant',
        'rating' => 'Mieux notés',
    ];

    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->query($filters)->paginate($perPage)->withQueryString();
    }

    public function query(array $filters): Builder
    {
        $query = Product::query()->published()->forCard();

        if (filled($filters['q'] ?? null)) {
            $this->applySearch($query, $filters['q']);
        }

        if (($filters['category'] ?? null) instanceof Category) {
            $query->whereIn('category_id', $filters['category']->descendantIds());
        }

        if (filled($filters['min_price'] ?? null)) {
            $query->whereRaw($this->priceSql().' >= ?', [(int) $filters['min_price']]);
        }

        if (filled($filters['max_price'] ?? null)) {
            $query->whereRaw($this->priceSql().' <= ?', [(int) $filters['max_price']]);
        }

        if (! empty($filters['in_stock'])) {
            $query->inStock();
        }

        if (! empty($filters['on_sale'])) {
            $query->onSale();
        }

        return $this->applySort($query, $filters['sort'] ?? 'popular');
    }

    /** Quick results for the instant-search dropdown. */
    public function suggestions(string $term, int $limit = 6): array
    {
        $products = Product::query()->published()->with('primaryImage')
            ->tap(fn (Builder $q) => $this->applySearch($q, $term))
            ->orderByDesc('sales_count')->limit($limit)->get();

        $categories = Category::query()->active()
            ->where(fn (Builder $q) => $this->whereContains($q, 'name', $term))
            ->ordered()->limit(3)->get(['id', 'name', 'slug']);

        return [
            'products' => $products->map(fn (Product $p) => [
                'name' => $p->name,
                'url' => route('products.show', $p),
                'image' => $p->image_url,
                'price' => money($p->currentPrice()),
            ])->all(),
            'categories' => $categories->map(fn (Category $c) => [
                'name' => $c->name,
                'url' => route('catalog.category', $c),
            ])->all(),
            'did_you_mean' => $products->isEmpty() ? $this->didYouMean($term) : null,
        ];
    }

    /** Closest product name when a search returns nothing (typo tolerance). */
    public function didYouMean(string $term): ?string
    {
        $needle = $this->normalize($term);

        if (mb_strlen($needle) < 3) {
            return null;
        }

        $best = null;
        $bestScore = PHP_INT_MAX;

        foreach ($this->vocabulary() as $word) {
            $score = levenshtein($needle, $word);
            if ($score < $bestScore) {
                [$best, $bestScore] = [$word, $score];
            }
        }

        $tolerance = max(1, intdiv(mb_strlen($needle), 3));

        return $best !== null && $bestScore > 0 && $bestScore <= $tolerance ? $best : null;
    }

    private function applySearch(Builder $query, string $term): void
    {
        $words = collect(preg_split('/\s+/', trim($term)))
            ->map(fn (string $w) => mb_substr($w, 0, 50))
            ->filter(fn (string $w) => mb_strlen($w) >= 2)
            ->take(6);

        foreach ($words as $word) {
            $query->where(function (Builder $q) use ($word) {
                $this->whereContains($q, 'name', $word);
                $q->orWhere('sku', 'like', '%'.$this->escapeLike(mb_strtoupper($word)).'%');
                $this->whereContains($q, 'short_description', $word, 'or');
                $q->orWhereHas('category', fn (Builder $c) => $this->whereContains($c, 'name', $word));
            });
        }
    }

    /** Case- and accent-insensitive "contains", portable across PostgreSQL and SQLite. */
    private function whereContains(Builder $query, string $column, string $value, string $boolean = 'and'): void
    {
        $needle = '%'.$this->escapeLike($this->normalize($value)).'%';
        $grammar = $query->getQuery()->getGrammar();
        $wrapped = $grammar->wrap($query->getModel()->qualifyColumn($column));

        if ($query->getConnection()->getDriverName() === 'pgsql') {
            $from = 'àâäáãåçéèêëíìîïñóòôöõúùûüýÿœæ';
            $to = 'aaaaaaceeeeiiiinooooouuuuyyoa';
            $query->whereRaw("translate(lower({$wrapped}), '{$from}', '{$to}') like ?", [$needle], $boolean);
        } else {
            $query->whereRaw("shop_normalize({$wrapped}) like ? escape '\\'", [$needle], $boolean);
        }
    }

    private function applySort(Builder $query, string $sort): Builder
    {
        return match ($sort) {
            'newest' => $query->orderByDesc('published_at')->orderByDesc('id'),
            'price_asc' => $query->orderByRaw($this->priceSql().' asc')->orderBy('id'),
            'price_desc' => $query->orderByRaw($this->priceSql().' desc')->orderBy('id'),
            'rating' => $query->orderByDesc('rating_avg')->orderByDesc('reviews_count'),
            default => $query->orderByDesc('is_featured')->orderByDesc('sales_count')->orderByDesc('id'),
        };
    }

    /** Effective price in SQL (sale price when the promotion is active). */
    private function priceSql(): string
    {
        $now = "'".now()->toDateTimeString()."'";

        return "(CASE WHEN sale_price IS NOT NULL AND sale_price < price
            AND (sale_starts_at IS NULL OR sale_starts_at <= {$now})
            AND (sale_ends_at IS NULL OR sale_ends_at >= {$now}) THEN sale_price ELSE price END)";
    }

    /** @return array<int, string> */
    private function vocabulary(): array
    {
        return Cache::remember('catalog.vocabulary', now()->addMinutes(30), fn () => Product::query()->published()
            ->pluck('name')
            ->merge(Category::query()->active()->pluck('name'))
            ->flatMap(fn (string $name) => preg_split('/[\s\-\/]+/', $this->normalize($name)))
            ->filter(fn (string $w) => mb_strlen($w) >= 3)
            ->unique()->values()->all());
    }

    private function normalize(string $value): string
    {
        return Str::lower(Str::ascii(trim($value)));
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
