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

    /** Listing, filters and quick actions are handled live by App\Livewire\Admin\ProductTable. */
    public function index(): View
    {
        Gate::authorize('viewAny', Product::class);

        return view('admin.products.index');
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
