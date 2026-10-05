<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\Catalog\ProductManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private readonly ProductManager $products) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Product::class);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(ProductStatus::class)],
            'stock' => ['nullable', Rule::in(['low', 'out'])],
            'sort' => ['nullable', Rule::in(['newest', 'name', 'price', 'stock', 'sales'])],
        ]);

        $products = Product::query()
            ->with(['primaryImage', 'category:id,name'])
            ->withCount('variants')
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.addcslashes($term, '%_\\').'%')
                ->orWhere('sku', 'like', '%'.addcslashes(mb_strtoupper($term), '%_\\').'%')))
            ->when($filters['category'] ?? null, fn ($q, $id) => $q->where('category_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when(($filters['stock'] ?? null) === 'low', fn ($q) => $q->lowStock()->where('stock', '>', 0))
            ->when(($filters['stock'] ?? null) === 'out', fn ($q) => $q->where('stock', 0))
            ->tap(fn ($q) => match ($filters['sort'] ?? 'newest') {
                'name' => $q->orderBy('name'),
                'price' => $q->orderByDesc('price'),
                'stock' => $q->orderBy('stock'),
                'sales' => $q->orderByDesc('sales_count'),
                default => $q->latest('id'),
            })
            ->paginate(20)->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'filters' => $filters,
            'categories' => Category::query()->ordered()->pluck('name', 'id'),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Product(['status' => ProductStatus::Draft, 'stock' => 0]));
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = $this->products->save(new Product, $request->validated(), $request->file('images', []));

        return redirect()->route('admin.products.edit', $product)
            ->with('toast', ['type' => 'success', 'message' => 'Produit créé.']);
    }

    public function edit(Product $product): View
    {
        return $this->form($product->load(['images', 'variants']));
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $product = $this->products->save($product, $request->validated(), $request->file('images', []));

        return redirect()->route('admin.products.edit', $product)
            ->with('toast', ['type' => 'success', 'message' => 'Produit enregistré.']);
    }

    public function updateStatus(Request $request, Product $product): RedirectResponse
    {
        Gate::authorize('update', $product);

        $status = ProductStatus::from($request->validate(['status' => ['required', Rule::enum(ProductStatus::class)]])['status']);
        $product->forceFill([
            'status' => $status,
            'published_at' => $status === ProductStatus::Published ? ($product->published_at ?? now()) : $product->published_at,
        ])->save();

        return back()->with('toast', ['type' => 'success', 'message' => "« {$product->name} » : {$status->label()}."]);
    }

    public function destroy(Product $product): RedirectResponse
    {
        Gate::authorize('delete', $product);

        $this->products->delete($product);

        return redirect()->route('admin.products.index')
            ->with('toast', ['type' => 'success', 'message' => 'Produit supprimé. Les commandes passées conservent leur historique.']);
    }

    private function form(Product $product): View
    {
        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::query()->with('parent:id,name')->ordered()->get()
                ->mapWithKeys(fn (Category $c) => [$c->id => ($c->parent ? $c->parent->name.' › ' : '').$c->name]),
        ]);
    }
}
