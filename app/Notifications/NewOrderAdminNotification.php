<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOrderAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Nouvelle commande {$this->order->number} — ".money($this->order->total))
            ->line("Client : {$this->order->customer_name} ({$this->order->customer_phone})")
            ->line('Paiement : '.config("payments.gateways.{$this->order->payment_method}.label", $this->order->payment_method))
            ->action('Ouvrir la commande', route('admin.orders.show', $this->order));
    }
}
