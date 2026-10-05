<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->words(3, true));

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'sku' => 'SKU-'.Str::upper(Str::random(8)),
            'short_description' => fake()->sentence(14),
            'description' => fake()->paragraphs(3, true),
            'price' => fake()->numberBetween(20, 400) * 500,
            'stock' => fake()->numberBetween(5, 60),
            'status' => ProductStatus::Published,
            'published_at' => now()->subDays(fake()->numberBetween(1, 90)),
            'specifications' => [['label' => 'Garantie', 'value' => '12 mois']],
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => ProductStatus::Draft, 'published_at' => null]);
    }

    public function onSale(int $percent = 20): static
    {
        return $this->state(fn (array $attributes) => [
            'sale_price' => (int) round($attributes['price'] * (100 - $percent) / 100),
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['stock' => 0]);
    }
}
