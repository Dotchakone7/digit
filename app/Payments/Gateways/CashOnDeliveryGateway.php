<?php

namespace App\Payments\Gateways;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Payments\Data\PaymentInitiation;

/** Offline: the courier collects the money; staff confirms reception. */
class CashOnDeliveryGateway extends AbstractGateway
{
    public function initiate(Payment $payment): PaymentInitiation
    {
        return new PaymentInitiation(PaymentStatus::Pending);
    }

    public function requiresManualConfirmation(): bool
    {
        return true;
    }
}
