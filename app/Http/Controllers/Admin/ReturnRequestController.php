<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReturnStatus;
use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use App\Support\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReturnRequestController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->validate(['status' => ['nullable', Rule::enum(ReturnStatus::class)]])['status'] ?? null;

        return view('admin.returns.index', [
            'returns' => ReturnRequest::query()->with(['order:id,number,total,customer_name', 'user:id,name'])
                ->when($status, fn ($q) => $q->where('status', $status))
                ->latest()->paginate(20)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function update(Request $request, ReturnRequest $returnRequest): RedirectResponse
    {
        $allowed = array_map(fn ($s) => $s->value, $returnRequest->status->allowedTransitions());
        $request->merge(['refund_amount' => Money::toMinor($request->input('refund_amount'))]);

        $data = $request->validate([
            'status' => ['required', Rule::in($allowed)],
            'admin_note' => ['nullable', 'string', 'max:1000'],
            'refund_amount' => ['nullable', 'integer', 'min:0', 'max:'.$returnRequest->order->total],
        ], ['status.in' => 'Ce changement de statut n’est pas autorisé.'], ['refund_amount' => 'montant remboursé']);

        $status = ReturnStatus::from($data['status']);
        $returnRequest->forceFill([
            'status' => $status,
            'admin_note' => $data['admin_note'] ?? $returnRequest->admin_note,
            'refund_amount' => $status === ReturnStatus::Refunded ? ($data['refund_amount'] ?? $returnRequest->order->total) : $returnRequest->refund_amount,
            'resolved_at' => in_array($status, [ReturnStatus::Rejected, ReturnStatus::Refunded], true) ? now() : null,
        ])->save();

        return back()->with('toast', ['type' => 'success', 'message' => 'Demande de retour : '.$status->label().'.']);
    }
}
