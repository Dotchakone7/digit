<?php

return [

    /*
    | Optional shopping assistant (chat widget). Disabled by default.
    | "rules" = built-in FAQ/order-tracking answers using real shop data, no external API.
    | To plug an LLM (OpenAI, Anthropic Claude, local model…), implement
    | App\Assistant\Contracts\AssistantDriver and register it in "drivers".
    */
    'enabled' => (bool) env('ASSISTANT_ENABLED', false),
    'driver' => env('ASSISTANT_DRIVER', 'rules'),

    'drivers' => [
        'rules' => App\Assistant\Drivers\RuleBasedAssistant::class,
    ],

    // Credentials for future LLM drivers — read from .env only.
    'providers' => [
        'api_key' => env('ASSISTANT_API_KEY'),
        'model' => env('ASSISTANT_MODEL'),
        'base_url' => env('ASSISTANT_BASE_URL'),
    ],
];
