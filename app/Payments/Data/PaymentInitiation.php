<?php

namespace App\Payments\Data;

use App\Enums\PaymentStatus;

final readonly class PaymentInitiation
{
    public function __construct(
        public PaymentStatus $status = PaymentStatus::Pending,
        public ?string $redirectUrl = null,
        public ?string $providerReference = null,
        public array $meta = [],
    ) {}
}
