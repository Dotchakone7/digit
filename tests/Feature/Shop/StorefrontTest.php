<?php

namespace Tests\Feature\Shop;

use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use App\Services\OrderStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_render(): void
    {
        $product = $this->product(['name' => 'Casque Studio Pro']);
        $this->shippingMethod();

        $this->get(route('home'))->assertOk()->assertSee('Casque Studio Pro');
        $this->get(route('catalog.index'))->assertOk();
        $this->get(route('catalog.category', $product->category))->assertOk()->assertSee('Casque Studio Pro');
        $this->get(route('products.show', $product))->assertOk()->assertSee('"@type":"Product"', false);
        $this->get(route('pages.show', 'livraison'))->assertOk();
        $this->get(route('sitemap'))->assertOk()->assertSee(route('products.show', $product));
        $this->get('/robots.txt')->assertOk();
        $this->get('/page-inexistante')->assertNotFound()->assertSee('Page introuvable');
    }

    public function test_structured_data_is_valid_json_ld(): void
    {
        $product = $this->product();

        foreach ([route('home'), route('products.show', $product)] as $url) {
            preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $this->get($url)->getContent(), $blocks);

            $this->assertNotEmpty($blocks[1], $url);
            foreach ($blocks[1] as $json) {
                $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
                $this->assertSame('https://schema.org', $data['@context'], $url); // Blade must not compile "@context" as a directive.
            }
        }
    }

    public function test_search_is_accent_and_case_insensitive_and_suggests_typos(): void
    {
        $this->product(['name' => 'Crème au karité']);

        $this->get(route('catalog.index', ['q' => 'CREME']))->assertSee('Crème au karité');
        $this->getJson(route('search.suggestions', ['q' => 'karite']))->assertJsonPath('products.0.name', 'Crème au karité');
        $this->getJson(route('search.suggestions', ['q' => 'karitr']))->assertJsonPath('did_you_mean', 'karite');
    }

    public function test_catalog_filters_by_price_and_availability(): void
    {
        $this->product(['name' => 'Article bon marché', 'price' => 5000]);
        $this->product(['name' => 'Article cher', 'price' => 90000]);
        $this->product(['name' => 'Article épuisé', 'price' => 6000, 'stock' => 0]);

        $this->get(route('catalog.index', ['max_price' => 10000, 'in_stock' => 1]))
            ->assertSee('Article bon marché')->assertDontSee('Article cher')->assertDontSee('Article épuisé');
    }

    public function test_draft_products_are_hidden(): void
    {
        $draft = $this->product(['status' => 'draft', 'name' => 'Produit secret']);

        $this->get(route('products.show', $draft))->assertNotFound();
        $this->get(route('catalog.index'))->assertDontSee('Produit secret');
    }

    public function test_only_customers_who_received_the_product_can_review_it_and_reviews_are_moderated(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        $this->actingAs($customer);

        $this->post(route('reviews.store', $product), ['rating' => 5, 'comment' => 'Superbe produit, je recommande.'])->assertForbidden();

        $this->addToCart($product);
        $this->post(route('checkout.store'), $this->checkoutPayload($this->shippingMethod()));
        $order = Order::query()->sole();
        foreach ([OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered] as $status) {
            app(OrderStatusService::class)->transition($order, $status);
        }

        $this->post(route('reviews.store', $product), ['rating' => 4, 'comment' => 'Superbe produit, je recommande.'])->assertRedirect();
        $review = Review::query()->sole();
        $this->assertSame(ReviewStatus::Pending, $review->status);
        $this->get(route('products.show', $product))->assertDontSee('Superbe produit');

        $this->post(route('reviews.store', $product), ['rating' => 1, 'comment' => 'Deuxième avis interdit.'])->assertForbidden();

        $this->actingAs(User::factory()->manager()->create())->patch(route('admin.reviews.update', $review), ['status' => 'approved']);
        $this->assertSame(4.0, $product->fresh()->rating_avg);
        $this->assertSame(1, $product->fresh()->reviews_count);
        auth()->logout();
        $this->get(route('products.show', $product))->assertSee('Superbe produit');
    }

    public function test_wishlist_persists_for_the_user(): void
    {
        $user = $this->customer();
        $product = $this->product();

        $this->postJson(route('wishlist.toggle', $product))->assertUnauthorized();
        $this->actingAs($user)->postJson(route('wishlist.toggle', $product))->assertJson(['active' => true]);
        $this->assertTrue($user->wishlist()->whereKey($product->id)->exists());
        $this->actingAs($user)->get(route('account.wishlist'))->assertSee($product->name);
        $this->actingAs($user)->postJson(route('wishlist.toggle', $product))->assertJson(['active' => false]);
    }

    public function test_newsletter_subscription_does_not_reveal_existing_emails(): void
    {
        $this->post(route('newsletter.store'), ['email' => 'Fan@Example.com'])->assertSessionHas('toast.type', 'success');
        $this->post(route('newsletter.store'), ['email' => 'fan@example.com'])->assertSessionHas('toast.type', 'success');

        $this->assertDatabaseCount('newsletter_subscribers', 1);
    }
}
