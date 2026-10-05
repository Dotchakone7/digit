<?php

namespace App\Delivery\Contracts;

use App\Models\Order;

/**
 * A delivery partner. The current "link" driver only redirects staff to the
 * partner's platform/phone/WhatsApp; an API driver could later book the
 * pickup automatically (createShipment) and pull tracking updates.
 */
interface CourierProvider
{
    public function name(): ?string;

    public function isConfigured(): bool;

    /**
     * Ways to reach the courier for this order.
     *
     * @return list<array{type: string, label: string, url: string}>
     */
    public function contactOptions(Order $order): array;

    public function supportsApiBooking(): bool;
}
