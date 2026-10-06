<?php

namespace App\Livewire\Admin;

use App\Enums\ProductStatus;
use App\Livewire\Concerns\AuthorizesAbility;
use App\Livewire\Concerns\WithTableState;
use App\Models\Category;
use App\Models\Product;
use App\Services\Catalog\ProductManager;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;

class ProductTable extends Component
{
    use AuthorizesAbility, WithTableState;

    #[Url(as: 'categorie', except: '')]
    public string $category = '';

    #[Url(as: 'statut', except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $stock = '';

    /** @var list<int> */
    public array $selected = [];

    protected function ability(): string
    {
        return 'catalog.manage';
    }

    protected function sortable(): array
    {
        return ['name', 'price', 'stock', 'sales_count'];
    }

    public function setStatus(int $id, string $status): void
    {
        $product = $this->find($id);
        $status = ProductStatus::from($status);

        $product->forceFill([
            'status' => $status,
            'published_at' => $status === ProductStatus::Published ? ($product->published_at ?? now()) : $product->published_at,
        ])->save();

        $this->toast("« {$product->name} » : {$status->label()}.");
    }

    public function updateStock(int $id, mixed $value): void
    {
        $product = $this->find($id);

        if ($product->variants()->exists()) {
            $this->toast('Ce produit a des variantes : modifiez le stock de chaque variante.', 'error');

            return;
        }

        if (! is_numeric($value) || (int) $value < 0 || (int) $value > 1_000_000) {
            $this->toast('Stock invalide.', 'error');

            return;
        }

        $product->forceFill(['stock' => (int) $value])->save();
        $this->toast("Stock de « {$product->name} » : {$product->stock}.");
    }

    public function delete(int $id, ProductManager $products): void
    {
        $product = $this->find($id, 'delete');
        $products->delete($product);
        $this->selected = array_values(array_diff($this->selected, [$id]));

        $this->toast('Produit supprimé. Les commandes passées gardent leur historique.');
    }

    public function bulkStatus(string $status): void
    {
        $status = ProductStatus::from($status);
        $count = 0;

        Product::query()->whereKey($this->selected)->get()->each(function (Product $product) use ($status, &$count) {
            if (Gate::allows('update', $product)) {
                $product->forceFill([
                    'status' => $status,
                    'published_at' => $status === ProductStatus::Published ? ($product->published_at ?? now()) : $product->published_at,
                ])->save();
                $count++;
            }
        });

        $this->selected = [];
        $this->toast("{$count} produit(s) : {$status->label()}.");
    }

    public function render(): View
    {
        $products = Product::query()
            ->with(['primaryImage', 'category:id,name'])
            ->withCount('variants')
            ->when(trim($this->search) !== '', fn ($q) => $q->where(fn ($w) => $w
                ->whereLike('name', $this->like(), caseSensitive: false)
                ->orWhereLike('sku', $this->like(), caseSensitive: false)))
            ->when(ctype_digit($this->category), fn ($q) => $q->where('category_id', (int) $this->category))
            ->when(ProductStatus::tryFrom($this->status), fn ($q, $status) => $q->where('status', $status))
            ->when($this->stock === 'low', fn ($q) => $q->lowStock()->where('stock', '>', 0))
            ->when($this->stock === 'out', fn ($q) => $q->where('stock', 0))
            ->tap(fn ($q) => $this->applySort($q))
            ->paginate(20);

        return view('livewire.admin.product-table', [
            'products' => $products,
            'categories' => Category::query()->ordered()->pluck('name', 'id'),
        ]);
    }

    private function find(int $id, string $ability = 'update'): Product
    {
        $product = Product::query()->findOrFail($id);
        Gate::authorize($ability, $product);

        return $product;
    }
}
