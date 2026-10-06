<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function index(): View
    {
        // Table rendered by the Livewire component App\Livewire\Admin\PaymentTable.
        return view('admin.payments.index');
    }

    /** Manual confirmation (cash collected, Mobile Money transfer checked on the operator statement). */
    public function confirm(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($payment->status->isOpen(), 422, 'Ce paiement est déjà clôturé.');

        $this->payments->markPaid($payment, $request->user());

        return back()->with('toast', ['type' => 'success', 'message' => "Paiement {$payment->reference} confirmé."]);
    }

    public function reject(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($payment->status->isOpen(), 422, 'Ce paiement est déjà clôturé.');

        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:200']])['reason'] ?? 'Rejeté après vérification';
        $this->payments->markFailed($payment, $reason);

        return back()->with('toast', ['type' => 'success', 'message' => "Paiement {$payment->reference} rejeté."]);
    }
}
