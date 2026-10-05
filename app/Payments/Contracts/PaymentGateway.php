<?php

namespace App\Payments\Contracts;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Payments\Data\PaymentInitiation;
use App\Payments\Data\WebhookResult;
use Illuminate\Http\Request;

/**
 * Contract every payment provider must implement.
 *
 * Golden rule: a payment is only marked "paid" from a server-side source
 * of truth (verified webhook, provider status API, or a staff member's
 * manual verification) — never because the customer came back to a URL.
 */
interface PaymentGateway
{
    public function code(): string;

    public function label(): string;

    public function description(): string;

    /** Whether the gateway is correctly configured and may be offered at checkout. */
    public function isAvailable(): bool;

    /** Creates the payment on the provider side (or prepares instructions). */
    public function initiate(Payment $payment): PaymentInitiation;

    /**
     * Parses and authenticates an incoming webhook. Must throw
     * InvalidWebhookSignature when the request cannot be trusted.
     */
    public function parseWebhook(Request $request): WebhookResult;

    /**
     * Asks the provider for the real status of the payment. Returns null
     * when the provider offers no verification API (offline methods).
     */
    public function fetchStatus(Payment $payment): ?PaymentStatus;

    /** Whether a staff member must manually confirm reception of funds. */
    public function requiresManualConfirmation(): bool;

    /** Minutes before an unpaid payment expires, or null if it never expires. */
    public function expiresAfterMinutes(): ?int;
}
