<?php

namespace App\Services\Catalog;

use App\Enums\ProductStatus;
use App\Exceptions\BusinessException;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/** Back-office write operations on products. */
class ProductManager
{
    public function __construct(private readonly ImageService $images) {}

    /** @param list<UploadedFile> $uploads */
    public function save(Product $product, array $data, array $uploads = []): Product
    {
        $variants = $data['variants'] ?? [];
        unset($data['variants'], $data['images']);
        $data['is_featured'] = (bool) ($data['is_featured'] ?? false);

        if (($data['status'] ?? null) === ProductStatus::Published->value && ! $product->published_at) {
            $data['published_at'] = now();
        }

        $this->assertVariantSkusAvailable($product, $variants);

        // Images are encoded before the transaction (slow I/O) and cleaned up if it fails.
        $paths = array_map(fn (UploadedFile $file) => $this->images->store($file, 'products'), $uploads);

        try {
            DB::transaction(function () use ($product, $data, $variants, $paths) {
                $product->fill($data)->save();
                $this->syncVariants($product, $variants);
                $this->attachImages($product, $paths, $data['name']);
            });
        } catch (\Throwable $e) {
            array_walk($paths, fn (string $path) => $this->images->delete($path));
            throw $e;
        }

        return $product->refresh();
    }

    public function addImages(Product $product, array $uploads): void
    {
        $paths = array_map(fn (UploadedFile $file) => $this->images->store($file, 'products'), $uploads);
        DB::transaction(fn () => $this->attachImages($product, $paths, $product->name));
    }

    public function deleteImage(ProductImage $image): void
    {
        $product = $image->product;

        DB::transaction(function () use ($image, $product) {
            $wasPrimary = $image->is_primary;
            $image->delete();

            if ($wasPrimary && ($next = $product->images()->reorder()->orderBy('position')->first())) {
                $next->forceFill(['is_primary' => true])->save();
            }
        });

        $this->images->delete($image->path);
    }

    public function setPrimaryImage(ProductImage $image): void
    {
        DB::transaction(function () use ($image) {
            ProductImage::query()->where('product_id', $image->product_id)->update(['is_primary' => false]);
            $image->forceFill(['is_primary' => true])->save();
        });
    }

    public function delete(Product $product): void
    {
        // Soft delete: past orders keep their snapshot, the product disappears from the shop.
        DB::transaction(function () use ($product) {
            $product->forceFill(['status' => ProductStatus::Archived])->save();
            $product->delete();
        });
    }

    private function attachImages(Product $product, array $paths, string $alt): void
    {
        $position = (int) $product->images()->max('position');
        $hasPrimary = $product->images()->where('is_primary', true)->exists();

        foreach ($paths as $i => $path) {
            $product->images()->create([
                'path' => $path,
                'alt' => $alt,
                'position' => $position + $i + 1,
                'is_primary' => ! $hasPrimary && $i === 0,
            ]);
        }
    }

    private function syncVariants(Product $product, array $rows): void
    {
        $keep = [];

        foreach ($rows as $position => $row) {
            $variant = isset($row['id']) ? $product->variants()->find($row['id']) : null;
            $variant ??= new ProductVariant(['product_id' => $product->id]);
            $variant->fill([
                'product_id' => $product->id,
                'name' => $row['name'],
                'sku' => $row['sku'],
                'price' => $row['price'] ?? null,
                'stock' => (int) $row['stock'],
                'is_active' => (bool) ($row['is_active'] ?? true),
                'position' => $position,
            ])->save();
            $keep[] = $variant->id;
        }

        $product->variants()->whereNotIn('id', $keep)->delete();

        // With variants, the product stock is the sum of its active variants.
        if ($keep !== []) {
            $product->forceFill(['stock' => (int) $product->variants()->where('is_active', true)->sum('stock')])->saveQuietly();
        }
    }

    private function assertVariantSkusAvailable(Product $product, array $rows): void
    {
        $skus = array_column($rows, 'sku');

        if ($skus === []) {
            return;
        }

        $taken = ProductVariant::query()->whereIn('sku', $skus)
            ->when($product->exists, fn ($q) => $q->where('product_id', '!=', $product->id))
            ->pluck('sku');

        if ($taken->isNotEmpty()) {
            throw new BusinessException('Référence de variante déjà utilisée : '.$taken->implode(', '));
        }
    }
}
