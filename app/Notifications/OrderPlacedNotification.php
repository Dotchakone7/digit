<?php

namespace App\Notifications;

class OrderPlacedNotification extends OrderNotification
{
    protected function subject(): string
    {
        return "Commande {$this->order->number} reçue";
    }

    protected function headline(): string
    {
        return "Nous avons bien reçu votre commande {$this->order->number}.";
    }

    protected function lines(): array
    {
        return [
            'Montant total : '.money($this->order->total),
            'Livraison : '.$this->order->shipping_method_name.' — '.$this->order->shippingAddressLine(),
            'Vous serez informé(e) à chaque étape de son traitement.',
        ];
    }
}
