<?php

namespace App\Http\Controllers\Account;

use App\Enums\ReturnStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ReturnRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReturnRequestController extends Controller
{
    public function create(Order $order): View
    {
        Gate::authorize('requestReturn', $order);

        return view('account.return', ['order' => $order->load('items'), 'reasons' => ReturnStatus::reasons()]);
    }

    public function store(Request $request, Order $order): RedirectResponse
    {
        Gate::authorize('requestReturn', $order);

        $data = $request->validate([
            'reason' => ['required', Rule::in(array_keys(ReturnStatus::reasons()))],
            'details' => ['nullable', 'string', 'max:2000'],
        ], [], ['reason' => 'motif', 'details' => 'précisions']);

        $return = new ReturnRequest($data + ['order_id' => $order->id, 'user_id' => $request->user()->id]);
        $return->status = ReturnStatus::Requested;
        $return->save();

        return redirect()->route('account.orders.show', $order)
            ->with('toast', ['type' => 'success', 'message' => 'Votre demande de retour a été envoyée. Nous revenons vers vous rapidement.']);
    }
}
