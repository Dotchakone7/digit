<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

final class Media
{
    public static function disk(): string
    {
        return config('shop.uploads.disk', 'public');
    }

    public static function url(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return Storage::disk(self::disk())->url($path);
    }
}
