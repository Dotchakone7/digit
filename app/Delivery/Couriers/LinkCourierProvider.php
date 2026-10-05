<?php

namespace App\Delivery\Couriers;

use App\Delivery\Contracts\CourierProvider;
use App\Models\Order;

class LinkCourierProvider implements CourierProvider
{
    public function name(): ?string
    {
        return setting('courier_name');
    }

    public function isConfigured(): bool
    {
        return filled(setting('courier_url')) || filled(setting('courier_phone')) || filled(setting('courier_whatsapp'));
    }

    public function contactOptions(Order $order): array
    {
        $options = [];

        if ($url = $this->safeUrl(setting('courier_url'))) {
            $options[] = ['type' => 'platform', 'label' => 'Ouvrir la plateforme de livraison', 'url' => $url];
        }

        if ($whatsapp = $this->digits(setting('courier_whatsapp'))) {
            $options[] = [
                'type' => 'whatsapp',
                'label' => 'Écrire sur WhatsApp',
                'url' => 'https://wa.me/'.$whatsapp.'?text='.rawurlencode($this->message($order)),
            ];
        }

        if ($phone = setting('courier_phone')) {
            $options[] = ['type' => 'phone', 'label' => 'Appeler le livreur', 'url' => 'tel:'.preg_replace('/[^0-9+]/', '', $phone)];
        }

        return $options;
    }

    public function supportsApiBooking(): bool
    {
        return false;
    }

    /** Pre-filled message so the courier gets everything needed for the pickup. */
    public function message(Order $order): string
    {
        $address = $order->shipping_address;

        return implode("\n", array_filter([
            "Bonjour, nouvelle livraison à effectuer pour la commande {$order->number}.",
            'Client : '.($address['full_name'] ?? $order->customer_name).' — '.($address['phone'] ?? $order->customer_phone),
            'Adresse : '.$order->shippingAddressLine(),
            ! empty($address['landmark']) ? 'Repère : '.$address['landmark'] : null,
            'Montant à encaisser : '.($order->payment_status->value === 'paid' ? '0 (déjà payé)' : money($order->total)),
        ]));
    }

    private function safeUrl(?string $url): ?string
    {
        return $url && preg_match('#^https?://#i', $url) ? $url : null;
    }

    private function digits(?string $value): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        return $digits !== '' ? $digits : null;
    }
}
