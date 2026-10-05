<?php

namespace Tests\Feature\Admin;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Casque Studio',
            'sku' => 'cas-001',
            'category_id' => Category::factory()->create()->id,
            'price' => '45 000',
            'sale_price' => '',
            'stock' => 12,
            'status' => 'published',
            'specifications' => [['label' => 'Autonomie', 'value' => '40 h'], ['label' => '', 'value' => '']],
        ], $overrides);
    }

    public function test_an_admin_can_create_a_product_with_images(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.products.store'), $this->payload([
            'images' => [UploadedFile::fake()->image('photo.jpg', 800, 800), UploadedFile::fake()->image('dos.png', 600, 600)],
        ]));

        $product = Product::query()->firstWhere('sku', 'CAS-001');
        $response->assertRedirect(route('admin.products.edit', $product));
        $this->assertSame(45000, $product->price);
        $this->assertSame('casque-studio', $product->slug);
        $this->assertNotNull($product->published_at);
        $this->assertCount(1, $product->specifications);
        $this->assertCount(2, $product->images);
        $this->assertSame(1, $product->images()->where('is_primary', true)->count());
        $path = $product->images->first()->path;
        $this->assertStringEndsWith('.webp', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_uploads_must_be_real_images(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.products.store'), $this->payload([
            'images' => [UploadedFile::fake()->create('virus.php', 10, 'application/x-php')],
        ]))->assertSessionHasErrors('images.0');

        $this->assertDatabaseCount('products', 0);
    }

    public function test_sale_price_must_be_lower_than_price(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.products.store'), $this->payload(['sale_price' => '50000']))
            ->assertSessionHasErrors('sale_price');
    }

    public function test_an_admin_can_update_a_product_and_its_variants(): void
    {
        $admin = User::factory()->admin()->create();
        $product = $this->product(['sku' => 'TSH-1']);

        $this->actingAs($admin)->put(route('admin.products.update', $product), $this->payload([
            'name' => 'T-shirt bio', 'sku' => 'TSH-1', 'price' => '9500',
            'variants' => [
                ['name' => 'Taille M', 'sku' => 'tsh-1-m', 'stock' => 4, 'is_active' => 1],
                ['name' => 'Taille L', 'sku' => 'tsh-1-l', 'stock' => 6, 'is_active' => 1, 'price' => '10500'],
            ],
        ]))->assertRedirect();

        $product->refresh();
        $this->assertSame('T-shirt bio', $product->name);
        $this->assertSame(9500, $product->price);
        $this->assertCount(2, $product->variants);
        $this->assertSame(10, $product->stock, 'Stock equals the sum of active variants.');
        $this->assertSame(10500, $product->variants->firstWhere('sku', 'TSH-1-L')->price);
    }

    public function test_an_admin_can_unpublish_and_delete_a_product(): void
    {
        $admin = User::factory()->admin()->create();
        $product = $this->product();

        $this->actingAs($admin)->post(route('admin.products.status', $product), ['status' => 'draft'])->assertRedirect();
        $this->assertSame(ProductStatus::Draft, $product->fresh()->status);
        $this->get(route('products.show', $product))->assertNotFound();

        $this->actingAs($admin)->delete(route('admin.products.destroy', $product))->assertRedirect(route('admin.products.index'));
        $this->assertSoftDeleted($product);
    }

    public function test_customers_cannot_create_products(): void
    {
        $this->actingAs($this->customer())->post(route('admin.products.store'), $this->payload())->assertForbidden();
        $this->assertDatabaseCount('products', 0);
    }
}
