<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\AddressRequest;
use App\Models\Address;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AddressController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.addresses', [
            'addresses' => $request->user()->addresses()->orderByDesc('is_default')->latest()->get(),
        ]);
    }

    public function store(AddressRequest $request): RedirectResponse
    {
        $user = $request->user();
        $address = $user->addresses()->make($request->safe()->except('is_default'));
        $address->is_default = false;
        $address->save();

        if ($request->boolean('is_default') || $user->addresses()->count() === 1) {
            $this->setDefault($address);
        }

        return back()->with('toast', ['type' => 'success', 'message' => 'Adresse ajoutée.']);
    }

    public function update(AddressRequest $request, Address $address): RedirectResponse
    {
        Gate::authorize('update', $address);

        $address->update($request->safe()->except('is_default'));

        if ($request->boolean('is_default')) {
            $this->setDefault($address);
        }

        return back()->with('toast', ['type' => 'success', 'message' => 'Adresse mise à jour.']);
    }

    public function destroy(Address $address): RedirectResponse
    {
        Gate::authorize('delete', $address);

        $wasDefault = $address->is_default;
        $user = $address->user;
        $address->delete();

        if ($wasDefault && ($next = $user->addresses()->latest()->first())) {
            $this->setDefault($next);
        }

        return back()->with('toast', ['type' => 'success', 'message' => 'Adresse supprimée.']);
    }

    public function makeDefault(Address $address): RedirectResponse
    {
        Gate::authorize('update', $address);
        $this->setDefault($address);

        return back()->with('toast', ['type' => 'success', 'message' => 'Adresse par défaut mise à jour.']);
    }

    private function setDefault(Address $address): void
    {
        DB::transaction(function () use ($address) {
            Address::query()->where('user_id', $address->user_id)->update(['is_default' => false]);
            $address->forceFill(['is_default' => true])->save();
        });
    }
}
