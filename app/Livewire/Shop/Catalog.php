<?php

namespace App\Livewire\Shop;

use App\Models\Category;
use App\Services\Catalog\ProductSearch;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Catalog with live filters: every change updates the results and the URL
 * (shareable, back-button friendly) without reloading the page.
 */
class Catalog extends Component
{
    use WithPagination;

    #[Locked]
    public ?int $categoryId = null;

    #[Url(except: '')]
    public string $q = '';

    #[Url(as: 'min_price', except: '')]
    public string $minPrice = '';

    #[Url(as: 'max_price', except: '')]
    public string $maxPrice = '';

    #[Url(as: 'in_stock', except: false)]
    public bool $inStock = false;

    #[Url(as: 'on_sale', except: false)]
    public bool $onSale = false;

    #[Url(except: 'popular')]
    public string $sort = 'popular';

    #[Url(except: 'grid')]
    public string $view = 'grid';

    public function mount(?Category $category = null): void
    {
        $this->categoryId = $category?->id;
    }

    /** Any filter change goes back to page 1. */
    public function updated(string $property): void
    {
        if ($property !== 'view') {
            $this->resetPage();
        }
    }

    public function clear(string $filter): void
    {
        match ($filter) {
            'q' => $this->q = '',
            'min_price' => $this->minPrice = '',
            'max_price' => $this->maxPrice = '',
            'in_stock' => $this->inStock = false,
            'on_sale' => $this->onSale = false,
            default => null,
        };
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('q', 'minPrice', 'maxPrice', 'inStock', 'onSale');
        $this->resetPage();
    }

    public function search(string $term): void
    {
        $this->q = mb_substr(trim($term), 0, 100);
        $this->resetPage();
    }

    public function render(ProductSearch $search): View
    {
        $category = $this->categoryId
            ? Category::query()->with(['parent', 'children' => fn ($q) => $q->active()->ordered()])->find($this->categoryId)
            : null;

        $filters = $this->filters() + ['category' => $category];
        $products = $search->paginate($filters, (int) config('shop.catalog.per_page'));

        return view('livewire.shop.catalog', [
            'category' => $category,
            'products' => $products,
            'filters' => $filters,
            'sorts' => ProductSearch::SORTS,
            'categories' => Category::query()->active()->roots()->ordered()
                ->with(['children' => fn ($q) => $q->active()->ordered()])->get(['id', 'parent_id', 'name', 'slug']),
            'didYouMean' => $products->isEmpty() && filled($filters['q']) ? $search->didYouMean($filters['q']) : null,
            'activeFilterCount' => collect($filters)->only(['min_price', 'max_price', 'in_stock', 'on_sale'])->filter()->count(),
        ]);
    }

    /** User input is sanitized here: invalid values are simply ignored. */
    private function filters(): array
    {
        $price = fn (string $value) => is_numeric($value) && $value >= 0 ? Money::toMinor($value) : null;

        return [
            'q' => trim(mb_substr($this->q, 0, 100)) ?: null,
            'min_price' => $price($this->minPrice),
            'max_price' => $price($this->maxPrice),
            'in_stock' => $this->inStock,
            'on_sale' => $this->onSale,
            'sort' => array_key_exists($this->sort, ProductSearch::SORTS) ? $this->sort : 'popular',
        ];
    }

    public function paginationView(): string
    {
        return 'livewire.pagination';
    }
}
