<?php

use App\Services\SettingsService;
use App\Support\Money;

if (! function_exists('money')) {
    function money(?int $amount, bool $withSymbol = true): string
    {
        return Money::format($amount, $withSymbol);
    }
}

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return app(SettingsService::class)->get($key, $default);
    }
}
