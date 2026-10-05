<?php

namespace Tests\Feature\Shop;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Coupon;
use App\Models\Order;
use App\Notifications\OrderPlacedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_requires_authentication(): void
    {
        $this->get(route('checkout.show'))->assertRedirect(route('login'));
    }

    public function test_a_customer_can_place_an_order_with_correct_totals(): void
    {
        Notification::fake();
        $user = $this->customer();
        $method = $this->shippingMethod(['price' => 2500]);
        $a = $this->product(['price' => 15000, 'stock' => 5]);
        $b = $this->product(['price' => 10000, 'sale_price' => 7000, 'stock' => 3]);

        $this->actingAs($user);
        $this->addToCart($a, 2);
        $this->addToCart($b, 1);
        $this->get(route('checkout.show'))->assertOk()->assertSee('Finaliser ma commande');

        $response = $this->post(route('checkout.store'), $this->checkoutPayload($method, ['save_address' => 1]));

        $order = Order::query()->with('items')->sole();
        $response->assertRedirect(route('checkout.confirmation', $order));
        $this->assertSame(37000, $order->subtotal);
        $this->assertSame(2500, $order->shipping_total);
        $this->assertSame(39500, $order->total);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(7000, $order->items->firstWhere('product_id', $b->id)->unit_price, 'Price at time of purchase is stored.');
        $this->assertSame(3, $a->fresh()->stock);
        $this->assertSame(2, $b->fresh()->stock);
        $this->assertSame(0, $user->cart->items()->count());
        $this->assertSame(1, $order->statusHistories()->count());
        $this->assertSame(1, $user->addresses()->count());
        $this->assertSame(PaymentStatus::Pending, $order->payments()->sole()->status);
        Notification::assertSentTo($user, OrderPlacedNotification::class);

        $this->get(route('checkout.confirmation', $order))->assertOk()->assertSee($order->number);
    }

    public function test_later_price_changes_do_not_affect_placed_orders(): void
    {
        $this->actingAs($this->customer());
        $product = $this->product(['price' => 10000]);
        $this->addToCart($product);
        $this->post(route('checkout.store'), $this->checkoutPayload($this->shippingMethod()));

        $product->update(['price' => 99000]);

        $this->assertSame(10000, Order::query()->sole()->items()->sole()->unit_price);
    }

    public function test_free_shipping_threshold_and_coupon_are_applied(): void
    {
        $this->actingAs($this->customer());
        $method = $this->shippingMethod(['price' => 2000, 'free_over_amount' => 30000]);
        $coupon = Coupon::factory()->fixed(5000)->create(['code' => 'CINQ']);
        $this->addToCart($this->product(['price' => 40000]));
        $this->postJson(route('cart.coupon.apply'), ['code' => 'CINQ'])->assertOk();

        $this->post(route('checkout.store'), $this->checkoutPayload($method));

        $order = Order::query()->sole();
        $this->assertSame(5000, $order->discount_total);
        $this->assertSame(0, $order->shipping_total, '35 000 after discount is above the free shipping threshold.');
        $this->assertSame(35000, $order->total);
        $this->assertSame(1, $coupon->fresh()->used_count);
        $this->assertDatabaseHas('coupon_usages', ['coupon_id' => $coupon->id, 'order_id' => $order->id, 'discount_amount' => 5000]);
    }

    public function test_the_order_fails_cleanly_when_stock_ran_out_meanwhile(): void
    {
        $this->actingAs($this->customer());
        $product = $this->product(['stock' => 2]);
        $this->addToCart($product, 2);
        $product->update(['stock' => 1]); // Someone else bought one.

        $this->post(route('checkout.store'), $this->checkoutPayload($this->shippingMethod()))
            ->assertRedirect()->assertSessionHas('toast.type', 'error');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(1, $product->fresh()->stock);
    }

    public function test_checkout_rejects_unknown_payment_methods_and_foreign_addresses(): void
    {
        $other = $this->customer();
        $foreign = $other->addresses()->create(['full_name' => 'X', 'phone' => '0700000000', 'city' => 'Abidjan', 'street' => 'Y']);
        $this->actingAs($this->customer());
        $this->addToCart($this->product());

        $this->post(route('checkout.store'), $this->checkoutPayload($this->shippingMethod(), ['payment_method' => 'free_money', 'address_id' => $foreign->id]))
            ->assertSessionHasErrors(['payment_method', 'address_id']);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_a_customer_cannot_see_another_customers_order(): void
    {
        $this->actingAs($this->customer());
        $this->addToCart($this->product());
        $this->post(route('checkout.store'), $this->checkoutPayload($this->shippingMethod()));
        $order = Order::query()->sole();

        $this->actingAs($this->customer());
        $this->get(route('account.orders.show', $order))->assertForbidden();
        $this->get(route('checkout.confirmation', $order))->assertForbidden();
        $this->get(route('payments.show', $order))->assertForbidden();
    }

    public function test_a_customer_can_cancel_an_unpaid_order_and_stock_is_restored(): void
    {
        $user = $this->customer();
        $this->actingAs($user);
        $product = $this->product(['stock' => 5]);
        $this->addToCart($product, 2);
        $this->post(route('checkout.store'), $this->checkoutPayload($this->shippingMethod()));
        $order = Order::query()->sole();
        $this->assertSame(3, $product->fresh()->stock);

        $this->post(route('account.orders.cancel', $order))->assertRedirect();

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertSame(PaymentStatus::Cancelled, $order->payments()->sole()->status);
    }
}
