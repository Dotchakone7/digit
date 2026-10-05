<?php

namespace App\Services\Orders;

use App\Models\ShippingMethod;

final readonly class CheckoutData
{
    public function __construct(
        public string $customerName,
        public string $customerEmail,
        public string $customerPhone,
        /** @var array{full_name: string, phone: string, city: string, district: ?string, street: string, landmark: ?string} */
        public array $address,
        public ShippingMethod $shippingMethod,
        public string $paymentMethod,
        public ?string $notes = null,
    ) {}
}
