<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'gateway' => ['nullable', 'string', 'max:40'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return view('admin.payments.index', [
            'payments' => Payment::query()->with('order:id,number,customer_name,status')
                ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
                ->when($filters['gateway'] ?? null, fn ($q, $g) => $q->where('gateway', $g))
                ->when($filters['q'] ?? null, fn ($q, $t) => $q->where(fn ($w) => $w->whereLike('reference', '%'.addcslashes($t, '%_\\').'%', caseSensitive: false)
                    ->orWhereHas('order', fn ($o) => $o->whereLike('number', '%'.addcslashes($t, '%_\\').'%', caseSensitive: false))))
                ->latest('id')->paginate(25)->withQueryString(),
            'filters' => $filters,
            'toVerify' => Payment::query()->where('status', PaymentStatus::Processing)->count(),
        ]);
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
