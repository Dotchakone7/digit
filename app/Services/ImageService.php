<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Support\Media;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores uploaded images safely: the file is decoded and re-encoded by GD,
 * which strips metadata (EXIF/GPS) and any payload hidden in the file,
 * resizes oversized pictures and gives a random, non-guessable name.
 */
class ImageService
{
    private const MAX_PIXELS = 40_000_000;

    public function store(UploadedFile $file, string $directory): string
    {
        $image = $this->load($file);
        $image = $this->resize($image, (int) config('shop.uploads.max_dimension', 1600));

        [$extension, $binary] = $this->encode($image);
        imagedestroy($image);

        $path = trim($directory, '/').'/'.Str::uuid()->toString().'.'.$extension;
        Storage::disk(Media::disk())->put($path, $binary, 'public');

        return $path;
    }

    public function delete(?string $path): void
    {
        if (filled($path) && ! str_starts_with($path, 'http')) {
            Storage::disk(Media::disk())->delete($path);
        }
    }

    private function load(UploadedFile $file): GdImage
    {
        $info = @getimagesize($file->getRealPath());

        if ($info === false || $info[0] * $info[1] > self::MAX_PIXELS) {
            throw new BusinessException('Image invalide ou trop grande.');
        }

        $image = match ($info['mime']) {
            'image/jpeg' => @imagecreatefromjpeg($file->getRealPath()),
            'image/png' => @imagecreatefrompng($file->getRealPath()),
            'image/webp' => @imagecreatefromwebp($file->getRealPath()),
            default => false,
        };

        if (! $image instanceof GdImage) {
            throw new BusinessException('Format d’image non pris en charge (JPG, PNG ou WebP).');
        }

        return $image;
    }

    private function resize(GdImage $image, int $max): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if (max($width, $height) <= $max) {
            return $image;
        }

        $ratio = $max / max($width, $height);
        $resized = imagecreatetruecolor((int) round($width * $ratio), (int) round($height * $ratio));
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, imagesx($resized), imagesy($resized), $width, $height);
        imagedestroy($image);

        return $resized;
    }

    /** @return array{0: string, 1: string} */
    private function encode(GdImage $image): array
    {
        $quality = (int) config('shop.uploads.quality', 82);
        ob_start();

        if (function_exists('imagewebp')) {
            imagewebp($image, null, $quality);
            $extension = 'webp';
        } else {
            imagejpeg($image, null, $quality);
            $extension = 'jpg';
        }

        return [$extension, (string) ob_get_clean()];
    }
}
