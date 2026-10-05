<?php

namespace App\Support;

final class Money
{
    /** Formats an integer amount expressed in the currency minor unit. */
    public static function format(?int $amount, bool $withSymbol = true): string
    {
        $decimals = (int) config('shop.currency.decimals');
        $value = ($amount ?? 0) / (10 ** $decimals);
        // Narrow no-break space as thousands separator (French typography).
        $formatted = number_format($value, $decimals, ',', "\u{202F}");

        return $withSymbol ? $formatted."\u{00A0}".config('shop.currency.symbol') : $formatted;
    }

    /** Converts a user-entered major-unit amount ("12 500", "12500,50") to minor units. */
    public static function toMinor(string|int|float|null $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = str_replace([' ', "\u{202F}", "\u{00A0}", ','], ['', '', '', '.'], (string) $value);

        return (int) round(((float) $normalized) * (10 ** (int) config('shop.currency.decimals')));
    }

    public static function toMajor(?int $amount): string
    {
        if ($amount === null) {
            return '';
        }
        $decimals = (int) config('shop.currency.decimals');

        return $decimals === 0 ? (string) $amount : number_format($amount / (10 ** $decimals), $decimals, '.', '');
    }
}
