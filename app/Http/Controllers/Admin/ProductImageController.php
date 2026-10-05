<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\Catalog\ProductManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductImageController extends Controller
{
    public function __construct(private readonly ProductManager $products) {}

    public function store(Request $request, Product $product): RedirectResponse
    {
        Gate::authorize('update', $product);

        $maxKb = (int) config('shop.uploads.max_kb');
        $request->validate([
            'images' => ['required', 'array', 'max:10'],
            'images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', "max:{$maxKb}", 'dimensions:min_width=200,min_height=200,max_width=8000,max_height=8000'],
        ]);

        $this->products->addImages($product, $request->file('images'));

        return back()->with('toast', ['type' => 'success', 'message' => 'Images ajoutées.']);
    }

    public function primary(Product $product, ProductImage $image): RedirectResponse
    {
        Gate::authorize('update', $product);
        abort_unless($image->product_id === $product->id, 404);

        $this->products->setPrimaryImage($image);

        return back()->with('toast', ['type' => 'success', 'message' => 'Image principale mise à jour.']);
    }

    public function destroy(Product $product, ProductImage $image): RedirectResponse
    {
        Gate::authorize('update', $product);
        abort_unless($image->product_id === $product->id, 404);

        $this->products->deleteImage($image);

        return back()->with('toast', ['type' => 'success', 'message' => 'Image supprimée.']);
    }
}
