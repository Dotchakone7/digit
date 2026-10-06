<?php

namespace App\Livewire\Admin;

use App\Enums\PaymentStatus;
use App\Livewire\Concerns\AuthorizesAbility;
use App\Livewire\Concerns\WithTableState;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class PaymentTable extends Component
{
    use AuthorizesAbility, WithTableState;

    #[Url(as: 'statut', except: '')]
    public string $status = '';

    #[Url(as: 'moyen', except: '')]
    public string $gateway = '';

    protected function ability(): string
    {
        return 'payments.manage';
    }

    protected function sortable(): array
    {
        return ['created_at', 'amount'];
    }

    public function mount(): void
    {
        $this->status = PaymentStatus::tryFrom($this->status ?: (string) request()->query('status', ''))?->value ?? '';
    }

    /** Staff confirms that the money was really received (cash collected, transfer seen on the statement). */
    public function confirm(int $id, PaymentService $payments): void
    {
        $payment = Payment::query()->findOrFail($id);

        if (! $payment->status->isOpen()) {
            $this->toast('Ce paiement est déjà clôturé.', 'error');

            return;
        }

        $payments->markPaid($payment, auth()->user());
        $this->toast("Paiement {$payment->reference} confirmé.");
    }

    public function reject(int $id, PaymentService $payments): void
    {
        $payment = Payment::query()->findOrFail($id);

        if (! $payment->status->isOpen()) {
            $this->toast('Ce paiement est déjà clôturé.', 'error');

            return;
        }

        $payments->markFailed($payment, 'Rejeté après vérification');
        $this->toast("Paiement {$payment->reference} rejeté.");
    }

    public function render(): View
    {
        return view('livewire.admin.payment-table', [
            'payments' => Payment::query()->with('order:id,number,customer_name,status')
                ->when(PaymentStatus::tryFrom($this->status), fn ($q, $s) => $q->where('status', $s))
                ->when(array_key_exists($this->gateway, config('payments.gateways')), fn ($q) => $q->where('gateway', $this->gateway))
                ->when(trim($this->search) !== '', fn ($q) => $q->where(fn ($w) => $w->whereLike('reference', $this->like(), caseSensitive: false)
                    ->orWhereHas('order', fn ($o) => $o->whereLike('number', $this->like(), caseSensitive: false)
                        ->orWhereLike('customer_name', $this->like(), caseSensitive: false))))
                ->tap(fn ($q) => $this->applySort($q))
                ->paginate(25),
            'toVerify' => Payment::query()->where('status', PaymentStatus::Processing)->count(),
        ]);
    }
}
