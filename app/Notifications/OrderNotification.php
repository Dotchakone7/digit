<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Base class for customer notifications about an order. Channels come from
 * config('shop.notification_channels'); adding SMS/WhatsApp means adding a
 * channel class and a to{Channel}() method here, nothing else changes.
 */
abstract class OrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order)
    {
        $this->afterCommit();
    }

    abstract protected function subject(): string;

    abstract protected function headline(): string;

    /** @return list<string> */
    abstract protected function lines(): array;

    public function via(object $notifiable): array
    {
        $channels = config('shop.notification_channels', ['mail']);

        // On-demand notifiables (guest e-mails) have no database storage.
        return $notifiable instanceof \App\Models\User ? $channels : array_values(array_diff($channels, ['database']));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject($this->subject().' — '.config('shop.name'))
            ->greeting('Bonjour '.explode(' ', $this->order->customer_name)[0].',')
            ->line($this->headline());

        foreach ($this->lines() as $line) {
            $message->line($line);
        }

        return $message
            ->action('Suivre ma commande', route('account.orders.show', $this->order))
            ->salutation('Merci de votre confiance, l’équipe '.config('shop.name'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_number' => $this->order->number,
            'title' => $this->subject(),
            'message' => $this->headline(),
            'url' => route('account.orders.show', $this->order),
        ];
    }

    /** Plain text used by future SMS/WhatsApp channels. */
    public function toText(object $notifiable): string
    {
        return $this->headline().' '.route('account.orders.show', $this->order);
    }
}
