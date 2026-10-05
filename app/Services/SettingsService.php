<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Site settings editable by the owner from the back-office.
 * Each key falls back to the .env / config value when not set in database.
 */
class SettingsService
{
    private const CACHE_KEY = 'shop.settings';

    private ?array $values = null;

    /** @return array<string, string|null> key => config fallback path */
    public static function definitions(): array
    {
        return [
            // Homepage & content
            'announcement' => null,
            'hero_eyebrow' => null,
            'hero_title' => null,
            'hero_subtitle' => null,
            'hero_image' => null,
            'about_text' => null,
            // Contact
            'contact_email' => 'shop.contact.email',
            'contact_phone' => 'shop.contact.phone',
            'contact_whatsapp' => 'shop.contact.whatsapp',
            'contact_address' => 'shop.contact.address',
            'opening_hours' => 'shop.contact.opening_hours',
            // Courier ("Contacter un livreur")
            'courier_name' => 'delivery.courier.name',
            'courier_url' => 'delivery.courier.url',
            'courier_phone' => 'delivery.courier.phone',
            'courier_whatsapp' => 'delivery.courier.whatsapp',
            'courier_notes' => 'delivery.courier.notes',
            // Delivery information displayed to customers
            'delivery_info' => null,
        ];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->all()[$key] ?? null;

        if (filled($value)) {
            return $value;
        }

        $fallback = self::definitions()[$key] ?? null;

        return ($fallback ? config($fallback) : null) ?? $default;
    }

    /** @param array<string, mixed> $values */
    public function set(array $values): void
    {
        foreach ($values as $key => $value) {
            if (! array_key_exists($key, self::definitions())) {
                continue;
            }
            Setting::query()->updateOrCreate(['key' => $key], ['value' => filled($value) ? (string) $value : null]);
        }

        Cache::forget(self::CACHE_KEY);
        $this->values = null;
    }

    public function all(): array
    {
        if ($this->values !== null) {
            return $this->values;
        }

        if (! Schema::hasTable('settings')) {
            return $this->values = [];
        }

        return $this->values = Cache::rememberForever(
            self::CACHE_KEY,
            fn () => Setting::query()->pluck('value', 'key')->all(),
        );
    }
}
