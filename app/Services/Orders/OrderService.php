<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderPlaced;
use App\Exceptions\BusinessException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\CouponService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(private readonly CouponService $coupons) {}

    /**
     * Converts the cart into an order. Runs in a single transaction with
     * row locks on products/variants so two customers can never buy the
     * same last unit. Prices are re-read from the database (never from the client).
     */
    public function placeOrder(User $user, Cart $cart, CheckoutData $data): Order
    {
        $order = DB::transaction(function () use ($user, $cart, $data) {
            $items = $cart->items()->get();

            if ($items->isEmpty()) {
                throw new BusinessException('Votre panier est vide.');
            }

            [$products, $variants] = $this->lockStock($items);

            $lines = $items->map(fn (CartItem $item) => $this->buildLine($item, $products, $variants));
            $subtotal = (int) $lines->sum('line_total');

            [$coupon, $discount] = $this->resolveDiscount($cart, $subtotal, $user);
            $shipping = $data->shippingMethod->costFor($subtotal - $discount);

            $order = new Order([
                'number' => $this->newNumber(),
                'user_id' => $user->id,
                'customer_name' => $data->customerName,
                'customer_email' => $data->customerEmail,
                'customer_phone' => $data->customerPhone,
                'shipping_address' => $data->address,
                'shipping_method_id' => $data->shippingMethod->id,
                'shipping_method_name' => $data->shippingMethod->name,
                'payment_method' => $data->paymentMethod,
                'coupon_id' => $coupon?->id,
                'coupon_code' => $coupon?->code,
                'subtotal' => $subtotal,
                'discount_total' => $discount,
                'shipping_total' => $shipping,
                'total' => $subtotal - $discount + $shipping,
                'currency' => config('shop.currency.code'),
                'notes' => $data->notes,
            ]);
            $order->status = OrderStatus::Pending;
            $order->payment_status = PaymentStatus::Pending;
            $order->save();

            $order->items()->createMany($lines->all());
            $this->reserveStock($lines, $products, $variants);

            if ($coupon) {
                $coupon->usages()->create(['user_id' => $user->id, 'order_id' => $order->id, 'discount_amount' => $discount]);
                $coupon->increment('used_count');
            }

            $order->statusHistories()->create([
                'user_id' => $user->id,
                'to_status' => OrderStatus::Pending,
                'comment' => 'Commande créée',
                'created_at' => now(),
            ]);

            $cart->items()->delete();
            $cart->update(['coupon_id' => null]);

            return $order;
        });

        OrderPlaced::dispatch($order);

        return $order;
    }

    /** @return array{0: Collection<int, Product>, 1: Collection<int, ProductVariant>} */
    private function lockStock(Collection $items): array
    {
        // Lock in a deterministic order to avoid deadlocks between concurrent checkouts.
        $products = Product::query()->whereIn('id', $items->pluck('product_id')->unique()->sort()->values())
            ->orderBy('id')->lockForUpdate()->with('primaryImage')->get()->keyBy('id');

        $variants = ProductVariant::query()->whereIn('id', $items->pluck('product_variant_id')->filter()->unique()->sort()->values())
            ->orderBy('id')->lockForUpdate()->get()->keyBy('id');

        return [$products, $variants];
    }

    private function buildLine(CartItem $item, Collection $products, Collection $variants): array
    {
        /** @var Product|null $product */
        $product = $products->get($item->product_id);
        /** @var ProductVariant|null $variant */
        $variant = $item->product_variant_id ? $variants->get($item->product_variant_id) : null;

        if ($product === null || ! $product->isPublished()) {
            throw new BusinessException('Un produit de votre panier n’est plus disponible. Veuillez vérifier votre panier.');
        }

        if ($item->product_variant_id && ($variant === null || ! $variant->is_active)) {
            throw new BusinessException("L'option choisie pour « {$product->name} » n’est plus disponible.");
        }

        $stock = $variant?->stock ?? $product->stock;

        if ($item->quantity > $stock) {
            throw new BusinessException("Stock insuffisant pour « {$product->name} » : il reste {$stock} exemplaire(s).");
        }

        $variant?->setRelation('product', $product);
        $unitPrice = $variant?->currentPrice() ?? $product->currentPrice();

        return [
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'product_name' => $product->name,
            'variant_name' => $variant?->name,
            'sku' => $variant?->sku ?? $product->sku,
            'image_path' => $product->primaryImage?->path,
            'unit_price' => $unitPrice,
            'quantity' => $item->quantity,
            'line_total' => $unitPrice * $item->quantity,
        ];
    }

    /** @return array{0: ?Coupon, 1: int} */
    private function resolveDiscount(Cart $cart, int $subtotal, User $user): array
    {
        if (! $cart->coupon_id) {
            return [null, 0];
        }

        $coupon = Coupon::query()->lockForUpdate()->find($cart->coupon_id);

        if ($coupon === null) {
            return [null, 0];
        }

        // Throws a user-friendly error if the coupon became invalid meanwhile.
        $this->coupons->assertUsable($coupon, $subtotal, $user);

        return [$coupon, $this->coupons->discountFor($coupon, $subtotal)];
    }

    private function reserveStock(Collection $lines, Collection $products, Collection $variants): void
    {
        foreach ($lines as $line) {
            if ($line['product_variant_id']) {
                $variants->get($line['product_variant_id'])->decrement('stock', $line['quantity']);
            }

            $product = $products->get($line['product_id']);
            $product->stock = max(0, $product->stock - $line['quantity']);
            $product->sales_count += $line['quantity'];
            $product->saveQuietly();
        }
    }

    private function newNumber(): string
    {
        $prefix = config('shop.orders.number_prefix', 'CMD');

        do {
            $number = sprintf('%s-%s-%s', $prefix, now()->format('ymd'), strtoupper(Str::random(5)));
        } while (Order::query()->where('number', $number)->exists());

        return $number;
    }
}
