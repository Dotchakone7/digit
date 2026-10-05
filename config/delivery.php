<?php

use App\Delivery\Couriers\LinkCourierProvider;

return [

    /*
    | Courier used by the "Contacter un livreur" button in the order back-office.
    | Values here are defaults; the shop owner can override them from
    | Admin > Paramètres > Livraison (stored in the settings table).
    |
    | "link" driver = redirect to the configured platform / phone / WhatsApp.
    | A future API integration only needs a new App\Delivery\Contracts\CourierProvider.
    */
    'courier' => [
        'driver' => env('COURIER_DRIVER', 'link'),
        'name' => env('COURIER_NAME'),
        'url' => env('COURIER_URL'),
        'phone' => env('COURIER_PHONE'),
        'whatsapp' => env('COURIER_WHATSAPP'),
        'notes' => env('COURIER_NOTES'),
    ],

    'drivers' => [
        'link' => LinkCourierProvider::class,
    ],
];
