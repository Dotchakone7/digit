<?php

namespace Tests\Feature\Shop;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_can_add_a_product_and_get_server_side_totals(): void
    {
        $product = $this->product(['price' => 12000]);

        $this->addToCart($product, 2)->assertOk()
            ->assertJsonPath('cart.count', 2)
            ->assertJsonPath('cart.subtotal', 24000)
            ->assertJsonPath('cart.total', 24000);
    }

    public function test_the_sale_price_is_used_when_the_promotion_is_active(): void
    {
        $product = $this->product(['price' => 10000, 'sale_price' => 8000]);

        $this->addToCart($product)->assertJsonPath('cart.subtotal', 8000);
    }

    public function test_quantity_cannot_exceed_stock(): void
    {
        $product = $this->product(['stock' => 2]);

        $this->addToCart($product, 3)->assertStatus(422)->assertJsonFragment(['message' => 'Stock insuffisant : il reste 2 exemplaire(s).']);
        $this->addToCart($product, 2)->assertOk();
        $this->addToCart($product, 1)->assertStatus(422);
    }

    public function test_out_of_stock_and_unpublished_products_cannot_be_added(): void
    {
        $this->addToCart($this->product(['stock' => 0]))->assertStatus(422);
        $this->addToCart($this->product(['status' => 'draft']))->assertStatus(422);
    }

    public function test_a_variant_is_required_when_the_product_has_variants(): void
    {
        $product = $this->product();
        $variant = ProductVariant::query()->create(['product_id' => $product->id, 'name' => 'Taille M', 'sku' => 'V-M', 'stock' => 3, 'is_active' => true]);
        $otherVariant = ProductVariant::query()->create(['product_id' => $this->product()->id, 'name' => 'X', 'sku' => 'V-X', 'stock' => 3, 'is_active' => true]);

        $this->addToCart($product)->assertStatus(422);
        $this->addToCart($product, 1, $otherVariant->id)->assertStatus(422);
        $this->addToCart($product, 1, $variant->id)->assertOk()->assertJsonPath('cart.items.0.variant', 'Taille M');
    }

    public function test_quantities_can_be_updated_and_items_removed(): void
    {
        $product = $this->product(['price' => 5000]);
        $this->addToCart($product);
        $item = Cart::query()->first()->items()->first();

        $this->patchJson(route('cart.items.update', $item), ['quantity' => 4])->assertOk()->assertJsonPath('cart.subtotal', 20000);
        $this->deleteJson(route('cart.items.destroy', $item))->assertOk()->assertJsonPath('cart.count', 0);
    }

    public function test_a_visitor_cannot_modify_someone_elses_cart_item(): void
    {
        $this->actingAs($this->customer());
        $this->addToCart($this->product());
        $item = Cart::query()->first()->items()->first();

        $this->actingAs($this->customer());
        $this->patchJson(route('cart.items.update', $item), ['quantity' => 2])->assertNotFound();
        $this->deleteJson(route('cart.items.destroy', $item))->assertNotFound();
        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 1]);
    }

    public function test_coupons_are_validated_and_computed_server_side(): void
    {
        Coupon::factory()->create(['code' => 'DIX', 'value' => 10]);
        Coupon::factory()->fixed(5000)->create(['code' => 'MIN', 'min_order_amount' => 50000]);
        Coupon::factory()->create(['code' => 'OLD', 'ends_at' => now()->subDay()]);
        $this->addToCart($this->product(['price' => 20000]), 2);

        $this->postJson(route('cart.coupon.apply'), ['code' => 'nope'])->assertStatus(422);
        $this->postJson(route('cart.coupon.apply'), ['code' => 'OLD'])->assertStatus(422);
        $this->postJson(route('cart.coupon.apply'), ['code' => 'MIN'])->assertStatus(422);
        $this->postJson(route('cart.coupon.apply'), ['code' => 'dix'])->assertOk()
            ->assertJsonPath('cart.discount', 4000)
            ->assertJsonPath('cart.total', 36000);
    }

    public function test_the_guest_cart_is_merged_at_login(): void
    {
        $product = $this->product();
        $this->addToCart($product, 2);
        $user = $this->customer(['email' => 'aya@example.com']);

        $this->post(route('login'), ['email' => 'aya@example.com', 'password' => 'password']);

        $this->assertSame(2, (int) $user->cart->items()->sum('quantity'));
        $this->assertSame(1, Cart::query()->count());
    }
}
