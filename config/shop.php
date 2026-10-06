<?php

return [

    /*
    | Brand identity. Colors and fonts live in resources/css/theme.css
    | (single source of truth for the visual identity).
    */
    'name' => env('SHOP_NAME', env('APP_NAME', 'Digit')),
    'tagline' => env('SHOP_TAGLINE', 'Des produits choisis, livrés chez vous.'),
    'logo' => env('SHOP_LOGO'),           // e.g. "images/brand/logo.svg" (relative to /public). Empty = text logo.
    'logo_dark' => env('SHOP_LOGO_DARK'), // optional variant for dark backgrounds.
    'favicon' => env('SHOP_FAVICON', 'favicon.svg'),

    'currency' => [
        'code' => env('SHOP_CURRENCY', 'XOF'),
        'symbol' => env('SHOP_CURRENCY_SYMBOL', 'FCFA'),
        'decimals' => (int) env('SHOP_CURRENCY_DECIMALS', 0),
        'locale' => env('SHOP_CURRENCY_LOCALE', 'fr_FR'),
    ],

    'contact' => [
        'email' => env('SHOP_CONTACT_EMAIL'),
        'phone' => env('SHOP_CONTACT_PHONE'),
        'whatsapp' => env('SHOP_CONTACT_WHATSAPP'),
        'address' => env('SHOP_CONTACT_ADDRESS'),
        'opening_hours' => env('SHOP_OPENING_HOURS'),
    ],

    'social' => [
        'facebook' => env('SHOP_SOCIAL_FACEBOOK'),
        'instagram' => env('SHOP_SOCIAL_INSTAGRAM'),
        'tiktok' => env('SHOP_SOCIAL_TIKTOK'),
        'x' => env('SHOP_SOCIAL_X'),
        'youtube' => env('SHOP_SOCIAL_YOUTUBE'),
        'linkedin' => env('SHOP_SOCIAL_LINKEDIN'),
    ],

    'catalog' => [
        'per_page' => 12,
        'low_stock_threshold' => (int) env('SHOP_LOW_STOCK_THRESHOLD', 5),
        'max_quantity_per_line' => 20,
        'new_product_days' => 30,
    ],

    'orders' => [
        'number_prefix' => env('SHOP_ORDER_PREFIX', 'CMD'),
        'return_window_days' => (int) env('SHOP_RETURN_WINDOW_DAYS', 14),
    ],

    'uploads' => [
        'disk' => env('SHOP_MEDIA_DISK', 'public'),
        'max_kb' => (int) env('SHOP_UPLOAD_MAX_KB', 4096),
        'max_dimension' => 1600,
        'quality' => 82,
    ],

    /*
    | Channels used by order/payment notifications. Add "sms", "whatsapp"…
    | once a corresponding channel class is registered (see docs/ARCHITECTURE.md).
    */
    'notification_channels' => array_filter(explode(',', env('SHOP_NOTIFICATION_CHANNELS', 'mail,database'))),

    'admin_notification_email' => env('SHOP_ADMIN_NOTIFICATION_EMAIL'),

    /*
    | Reverse proxies / load balancers allowed to forward the client IP and
    | HTTPS scheme: "*" (e.g. behind Cloudflare or a PaaS) or a comma-separated IP list.
    | Read here (not in bootstrap/app.php) so it survives "php artisan optimize".
    */
    'trusted_proxies' => env('TRUSTED_PROXIES'),
];
