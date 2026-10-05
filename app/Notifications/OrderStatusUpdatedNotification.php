<?php

namespace App\Notifications;

use App\Enums\OrderStatus;
use App\Models\Order;

class OrderStatusUpdatedNotification extends OrderNotification
{
    public function __construct(Order $order, public OrderStatus $status, public ?string $comment = null)
    {
        parent::__construct($order);
    }

    protected function subject(): string
    {
        return match ($this->status) {
            OrderStatus::Shipped => "Votre commande {$this->order->number} est en route",
            OrderStatus::Delivered => "Commande {$this->order->number} livrée",
            OrderStatus::Cancelled => "Commande {$this->order->number} annulée",
            default => "Commande {$this->order->number} : {$this->status->label()}",
        };
    }

    protected function headline(): string
    {
        return match ($this->status) {
            OrderStatus::Confirmed => 'Votre commande est confirmée.',
            OrderStatus::Processing => 'Votre commande est en cours de préparation.',
            OrderStatus::Shipped => 'Votre commande a été remise au livreur et arrive bientôt.',
            OrderStatus::Delivered => 'Votre commande a été livrée. Nous espérons qu’elle vous plaît !',
            OrderStatus::Cancelled => 'Votre commande a été annulée.',
            OrderStatus::Refunded => 'Votre commande a été remboursée.',
            default => 'Le statut de votre commande a changé : '.$this->status->label().'.',
        };
    }

    protected function lines(): array
    {
        return array_filter([$this->comment]);
    }
}
