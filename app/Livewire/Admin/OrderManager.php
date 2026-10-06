<?php

namespace App\Livewire\Admin;

use App\Delivery\CourierManager;
use App\Enums\OrderStatus;
use App\Enums\ShipmentStatus;
use App\Exceptions\BusinessException;
use App\Livewire\Concerns\AuthorizesAbility;
use App\Models\Order;
use App\Models\Payment;
use App\Services\OrderStatusService;
use App\Services\PaymentService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Admin order page: status workflow, payment verification and shipments
 * without page reloads. Every state change still goes through
 * OrderStatusService / PaymentService (history, events, notifications).
 */
class OrderManager extends Component
{
    use AuthorizesAbility;

    #[Locked]
    public Order $order;

    public string $comment = '';

    public string $carrier = '';

    public string $trackingNumber = '';

    public string $shipmentNotes = '';

    protected function ability(): string
    {
        return 'orders.manage';
    }

    public function mount(): void
    {
        $this->carrier = (string) setting('courier_name');
    }

    public function changeStatus(string $status, OrderStatusService $statuses): void
    {
        $to = OrderStatus::tryFrom($status);
        $this->order->refresh();

        if ($to === null || ! in_array($to, $this->order->status->allowedTransitions(), true)) {
            $this->toast('Ce changement de statut n’est pas autorisé.', 'error');

            return;
        }

        $this->validate(['comment' => ['nullable', 'string', 'max:500']]);
        $comment = trim($this->comment) ?: ($to === OrderStatus::Cancelled ? 'Annulée par la boutique' : null);

        try {
            $statuses->transition($this->order, $to, auth()->user(), $comment);
        } catch (BusinessException $e) {
            $this->toast($e->getMessage(), 'error');

            return;
        }

        $this->reset('comment');
        $this->toast('Statut mis à jour : '.$to->label().'.');
    }

    public function confirmPayment(int $id, PaymentService $payments): void
    {
        if ($payment = $this->openPayment($id)) {
            $payments->markPaid($payment, auth()->user());
            $this->toast("Paiement {$payment->reference} confirmé.");
        }
    }

    public function rejectPayment(int $id, PaymentService $payments): void
    {
        if ($payment = $this->openPayment($id)) {
            $payments->markFailed($payment, 'Rejeté après vérification');
            $this->toast("Paiement {$payment->reference} rejeté.");
        }
    }

    public function addShipment(): void
    {
        if (in_array($this->order->fresh()->status, [OrderStatus::Cancelled, OrderStatus::Refunded, OrderStatus::Delivered], true)) {
            $this->toast('Cette commande est clôturée.', 'error');

            return;
        }

        $data = $this->validate([
            'carrier' => ['required', 'string', 'max:60'],
            'trackingNumber' => ['nullable', 'string', 'max:120'],
            'shipmentNotes' => ['nullable', 'string', 'max:500'],
        ], [], ['carrier' => 'livreur / transporteur', 'trackingNumber' => 'numéro de suivi', 'shipmentNotes' => 'note']);

        $this->order->shipments()->create([
            'carrier' => $data['carrier'],
            'tracking_number' => $data['trackingNumber'] ?: null,
            'notes' => $data['shipmentNotes'] ?: null,
            'status' => ShipmentStatus::Pending,
        ]);

        $this->reset('trackingNumber', 'shipmentNotes');
        $this->dispatch('shipment-saved');
        $this->toast('Expédition enregistrée. Passez la commande en « Expédiée » lors de la remise au livreur.');
    }

    public function render(CourierManager $couriers): View
    {
        $this->order->refresh()->load(['items', 'statusHistories.user:id,name', 'payments', 'shipments', 'user', 'returnRequests', 'coupon']);
        $courier = $couriers->provider();

        return view('livewire.admin.order-manager', [
            'transitions' => $this->order->status->allowedTransitions(),
            'courier' => $courier,
            'courierOptions' => $courier->isConfigured() ? $courier->contactOptions($this->order) : [],
        ]);
    }

    private function openPayment(int $id): ?Payment
    {
        Gate::authorize('payments.manage');

        $payment = $this->order->payments()->findOrFail($id);

        if (! $payment->status->isOpen()) {
            $this->toast('Ce paiement est déjà clôturé.', 'error');

            return null;
        }

        return $payment;
    }

    private function toast(string $message, string $type = 'success'): void
    {
        $this->dispatch('toast', message: $message, type: $type);
    }
}
