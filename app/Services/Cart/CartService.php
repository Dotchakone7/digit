<?php

namespace App\Services\Cart;

use App\Exceptions\BusinessException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\CouponService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CartService
{
    public const SESSION_KEY = 'cart_token';

    private ?Cart $cart = null;

    /** Owner the memoized cart belongs to (user id or guest token). */
    private ?string $cartOwner = null;

    public function __construct(private readonly CouponService $coupons) {}

    /** Current visitor cart (created lazily on first write). */
    public function current(bool $create = false): ?Cart
    {
        $user = auth()->user();
        $owner = $user ? 'user:'.$user->id : 'guest:'.session(self::SESSION_KEY);

        if ($this->cart !== null && $this->cartOwner === $owner) {
            return $this->cart;
        }

        $this->cart = null;
        $this->cartOwner = $owner;

        $cart = $user
            ? Cart::query()->firstWhere('user_id', $user->id)
            : (session()->has(self::SESSION_KEY) ? Cart::query()->firstWhere('session_id', session(self::SESSION_KEY)) : null);

        if ($cart === null && $create) {
            $cart = $user
                ? Cart::query()->create(['user_id' => $user->id])
                : Cart::query()->create(['session_id' => $this->sessionToken()]);
            $this->cartOwner = $user ? 'user:'.$user->id : 'guest:'.session(self::SESSION_KEY);
        }

        return $this->cart = $cart;
    }

    public function add(Product $product, ?ProductVariant $variant, int $quantity): CartItem
    {
        $this->assertSellable($product, $variant);

        $cart = $this->current(create: true);

        $item = $cart->items()->firstOrNew([
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
        ]);

        $newQuantity = ($item->exists ? $item->quantity : 0) + $quantity;
        $this->assertQuantity($product, $variant, $newQuantity);

        $item->quantity = $newQuantity;
        $item->save();
        $cart->touch();

        return $item;
    }

    public function update(CartItem $item, int $quantity): void
    {
        $this->assertOwnsItem($item);

        if ($quantity <= 0) {
            $this->remove($item);

            return;
        }

        $this->assertQuantity($item->product, $item->variant, $quantity);
        $item->update(['quantity' => $quantity]);
    }

    public function remove(CartItem $item): void
    {
        $this->assertOwnsItem($item);
        $item->delete();
    }

    public function clear(): void
    {
        $cart = $this->current();
        $cart?->items()->delete();
        $cart?->update(['coupon_id' => null]);
    }

    public function applyCoupon(string $code): void
    {
        $summary = $this->summary();

        if ($summary->isEmpty()) {
            throw new BusinessException('Votre panier est vide.');
        }

        $coupon = $this->coupons->findValid($code, $summary->subtotal, auth()->user());
        $this->current()->update(['coupon_id' => $coupon->id]);
    }

    public function removeCoupon(): void
    {
        $this->current()?->update(['coupon_id' => null]);
    }

    public function count(): int
    {
        $cart = $this->current();

        return $cart ? (int) $cart->items()->sum('quantity') : 0;
    }

    /** Computes all totals from current database prices. */
    public function summary(?ShippingMethod $shippingMethod = null): CartSummary
    {
        $cart = $this->current();

        if ($cart === null) {
            return new CartSummary(collect(), 0, 0, null, null, $shippingMethod);
        }

        $items = $cart->items()
            ->with(['product' => fn ($q) => $q->withTrashed()->with('primaryImage'), 'variant'])
            ->get()
            ->filter(fn (CartItem $item) => $item->product !== null && ! $item->product->trashed())
            ->values();

        $subtotal = (int) $items->sum(fn (CartItem $item) => $item->lineTotal());

        // Always re-read: the coupon may have been applied earlier in this same request.
        $coupon = $cart->coupon_id ? $cart->coupon()->first() : null;
        $discount = 0;

        if ($coupon && $this->coupons->isUsable($coupon, $subtotal, auth()->user())) {
            $discount = $this->coupons->discountFor($coupon, $subtotal);
        } elseif ($coupon) {
            $cart->update(['coupon_id' => null]);
            $coupon = null;
        }

        $shipping = $shippingMethod?->costFor($subtotal - $discount);

        return new CartSummary($items, $subtotal, $discount, $shipping, $coupon, $shippingMethod);
    }

    /** Moves the guest cart into the user's cart after login/registration. */
    public function mergeGuestCartInto(User $user): void
    {
        $token = session(self::SESSION_KEY);

        if (! $token) {
            return;
        }

        $guest = Cart::query()->with('items')->firstWhere('session_id', $token);

        if ($guest === null) {
            return;
        }

        DB::transaction(function () use ($guest, $user) {
            $cart = Cart::query()->firstOrCreate(['user_id' => $user->id]);

            foreach ($guest->items as $guestItem) {
                $item = $cart->items()->firstOrNew([
                    'product_id' => $guestItem->product_id,
                    'product_variant_id' => $guestItem->product_variant_id,
                ]);
                $max = (int) config('shop.catalog.max_quantity_per_line');
                $item->quantity = min(($item->exists ? $item->quantity : 0) + $guestItem->quantity, $max);
                $item->save();
            }

            if ($guest->coupon_id && ! $cart->coupon_id) {
                $cart->update(['coupon_id' => $guest->coupon_id]);
            }

            $guest->delete();
        });

        session()->forget(self::SESSION_KEY);
        $this->cart = null;
    }

    private function assertSellable(Product $product, ?ProductVariant $variant): void
    {
        if (! $product->isPublished()) {
            throw new BusinessException("Ce produit n'est plus disponible.");
        }

        if ($variant !== null && ($variant->product_id !== $product->id || ! $variant->is_active)) {
            throw new BusinessException("Cette variante n'est pas disponible.");
        }

        if ($variant === null && $product->activeVariants()->exists()) {
            throw new BusinessException('Veuillez choisir une option (taille, couleur…).');
        }
    }

    private function assertQuantity(Product $product, ?ProductVariant $variant, int $quantity): void
    {
        $stock = $variant?->stock ?? $product->stock;
        $max = (int) config('shop.catalog.max_quantity_per_line');

        if ($stock <= 0) {
            throw new BusinessException('Ce produit est en rupture de stock.');
        }

        if ($quantity > $stock) {
            throw new BusinessException("Stock insuffisant : il reste {$stock} exemplaire(s).");
        }

        if ($quantity > $max) {
            throw new BusinessException("Quantité maximale par article : {$max}.");
        }
    }

    private function assertOwnsItem(CartItem $item): void
    {
        $cart = $this->current();

        abort_unless($cart !== null && $item->cart_id === $cart->id, 404);
    }

    private function sessionToken(): string
    {
        if (! session()->has(self::SESSION_KEY)) {
            session()->put(self::SESSION_KEY, Str::random(40));
        }

        return session(self::SESSION_KEY);
    }
}
