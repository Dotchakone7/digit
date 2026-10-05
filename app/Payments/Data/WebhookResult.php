<?php

namespace App\Payments\Data;

use App\Enums\PaymentStatus;

final readonly class WebhookResult
{
    public function __construct(
        public string $reference,
        public PaymentStatus $status,
        public ?string $providerReference = null,
        public ?int $amount = null,
        public array $meta = [],
    ) {}
}
