<?php

return [

    /*
    | Gateways offered at checkout, in display order. Each key must exist in
    | "gateways" below and be enabled. Add a real provider (CinetPay, PayDunya,
    | FedaPay, Wave Business, Orange Money API…) by implementing
    | App\Payments\Contracts\PaymentGateway and registering it here.
    */
    'enabled' => array_filter(explode(',', env('PAYMENT_GATEWAYS', 'cash_on_delivery,manual_mobile_money'))),

    // Minutes before an unpaid online payment expires (and the order stock is released).
    'expiration_minutes' => (int) env('PAYMENT_EXPIRATION_MINUTES', 60),

    'gateways' => [

        'cash_on_delivery' => [
            'driver' => App\Payments\Gateways\CashOnDeliveryGateway::class,
            'label' => 'Paiement à la livraison',
            'description' => 'Réglez en espèces ou par Mobile Money à la réception de votre commande.',
        ],

        /*
        | The customer transfers the amount to the merchant's Mobile Money
        | number, then submits the transaction ID. The payment stays
        | "processing" until a staff member verifies it in the back-office.
        */
        'manual_mobile_money' => [
            'driver' => App\Payments\Gateways\ManualMobileMoneyGateway::class,
            'label' => 'Mobile Money (transfert)',
            'description' => 'Orange Money, MTN MoMo, Moov Money ou Wave : transférez puis indiquez la référence.',
            'operators' => [
                'orange' => ['label' => 'Orange Money', 'number' => env('MOMO_ORANGE_NUMBER')],
                'mtn' => ['label' => 'MTN Mobile Money', 'number' => env('MOMO_MTN_NUMBER')],
                'moov' => ['label' => 'Moov Money', 'number' => env('MOMO_MOOV_NUMBER')],
                'wave' => ['label' => 'Wave', 'number' => env('MOMO_WAVE_NUMBER')],
            ],
            'account_name' => env('MOMO_ACCOUNT_NAME'),
        ],

        /*
        | Local simulation of a hosted payment page + signed webhook.
        | NEVER enabled in production (enforced by PaymentManager).
        */
        'sandbox' => [
            'driver' => App\Payments\Gateways\SandboxGateway::class,
            'label' => 'Paiement test (sandbox)',
            'description' => 'Simulateur de prestataire pour le développement.',
            'secret' => env('PAYMENT_SANDBOX_SECRET'),
        ],
    ],
];
