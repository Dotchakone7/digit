<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\WishlistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function toggle(Request $request, Product $product, WishlistService $wishlist): JsonResponse|RedirectResponse
    {
        abort_unless($product->isPublished() || $request->user()->wishlist()->whereKey($product->id)->exists(), 404);

        $active = $wishlist->toggle($request->user(), $product);
        $message = $active ? 'Ajouté à vos favoris.' : 'Retiré de vos favoris.';

        return $request->expectsJson()
            ? response()->json(['active' => $active, 'message' => $message])
            : back()->with('toast', ['type' => 'success', 'message' => $message]);
    }
}
