<?php

namespace App\Notifications;

class PaymentConfirmedNotification extends OrderNotification
{
    protected function subject(): string
    {
        return "Paiement confirmé — {$this->order->number}";
    }

    protected function headline(): string
    {
        return 'Votre paiement de '.money($this->order->total)." pour la commande {$this->order->number} a été confirmé.";
    }

    protected function lines(): array
    {
        return ['Nous préparons maintenant votre commande.'];
    }
}
