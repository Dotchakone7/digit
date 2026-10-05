<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\CatalogFilterRequest;
use App\Models\Category;
use App\Services\Catalog\ProductSearch;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __construct(private readonly ProductSearch $search) {}

    public function index(CatalogFilterRequest $request): View
    {
        return $this->render($request);
    }

    public function category(CatalogFilterRequest $request, Category $category): View
    {
        abort_unless($category->is_active, 404);

        return $this->render($request, $category);
    }

    private function render(CatalogFilterRequest $request, ?Category $category = null): View
    {
        $filters = $request->filters() + ['category' => $category];
        $products = $this->search->paginate($filters, (int) config('shop.catalog.per_page'));

        return view('shop.catalog', [
            'category' => $category?->load(['parent', 'children' => fn ($q) => $q->active()]),
            'products' => $products,
            'filters' => $filters,
            'sorts' => ProductSearch::SORTS,
            'view' => $request->input('view') === 'list' ? 'list' : 'grid',
            'didYouMean' => $products->isEmpty() && filled($filters['q']) ? $this->search->didYouMean($filters['q']) : null,
            'categories' => Category::query()->active()->roots()->ordered()
                ->with(['children' => fn ($q) => $q->active()->ordered()])->get(['id', 'parent_id', 'name', 'slug']),
            'activeFilterCount' => collect($filters)->except(['sort', 'q', 'category'])->filter()->count(),
        ]);
    }
}
