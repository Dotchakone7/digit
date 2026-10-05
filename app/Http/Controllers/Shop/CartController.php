<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\AddToCartRequest;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Cart\CartService;
use App\Services\ShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

    public function index(ShippingService $shipping): View
    {
        $summary = $this->cart->summary();

        return view('shop.cart', [
            'summary' => $summary,
            'shippingQuotes' => $shipping->quotes($summary->subtotal - $summary->discount),
        ]);
    }

    public function summary(): JsonResponse
    {
        return response()->json($this->cart->summary()->toArray());
    }

    public function store(AddToCartRequest $request): JsonResponse|RedirectResponse
    {
        $product = Product::query()->findOrFail($request->integer('product_id'));
        $variant = $request->filled('variant_id') ? ProductVariant::query()->findOrFail($request->integer('variant_id')) : null;

        $this->cart->add($product, $variant, $request->integer('quantity'));

        return $this->respond($request, '« '.$product->name.' » a été ajouté à votre panier.');
    }

    public function update(Request $request, CartItem $item): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:'.config('shop.catalog.max_quantity_per_line')]]);

        $this->cart->update($item, $data['quantity']);

        return $this->respond($request, 'Quantité mise à jour.');
    }

    public function destroy(Request $request, CartItem $item): JsonResponse|RedirectResponse
    {
        $this->cart->remove($item);

        return $this->respond($request, 'Article retiré du panier.');
    }

    public function applyCoupon(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:40']], [], ['code' => 'code promo']);

        $this->cart->applyCoupon($data['code']);

        return $this->respond($request, 'Code promo appliqué.');
    }

    public function removeCoupon(Request $request): JsonResponse|RedirectResponse
    {
        $this->cart->removeCoupon();

        return $this->respond($request, 'Code promo retiré.');
    }

    private function respond(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'cart' => $this->cart->summary()->toArray()]);
        }

        return back()->with('toast', ['type' => 'success', 'message' => $message]);
    }
}
