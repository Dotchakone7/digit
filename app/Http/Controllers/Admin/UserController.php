<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RoleSlug;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::enum(RoleSlug::class)],
            'active' => ['nullable', Rule::in(['1', '0'])],
        ]);

        return view('admin.users.index', [
            'users' => User::query()->with('role')
                ->withCount('orders')
                ->withSum(['orders as revenue' => fn ($q) => $q->revenue()], 'total')
                ->when($filters['q'] ?? null, fn ($q, $t) => $q->where(fn ($w) => $w
                    ->whereLike('name', '%'.addcslashes($t, '%_\\').'%', caseSensitive: false)
                    ->orWhereLike('email', '%'.addcslashes($t, '%_\\').'%', caseSensitive: false)
                    ->orWhereLike('phone', '%'.addcslashes($t, '%_\\').'%', caseSensitive: false)))
                ->when($filters['role'] ?? null, fn ($q, $r) => $q->whereHas('role', fn ($w) => $w->where('slug', $r)))
                ->when(isset($filters['active']), fn ($q) => $q->where('is_active', $filters['active'] === '1'))
                ->latest('id')->paginate(20)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function show(User $user): View
    {
        Gate::authorize('view', $user);

        return view('admin.users.show', [
            'user' => $user->load(['role', 'addresses']),
            'orders' => $user->orders()->latest()->limit(10)->get(),
            'stats' => [
                'orders' => $user->orders()->count(),
                'revenue' => (int) $user->orders()->revenue()->sum('total'),
                'reviews' => $user->reviews()->count(),
            ],
            'roles' => $this->assignableRoles(request()),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $data = $request->validate([
            'role' => ['sometimes', Rule::in(array_keys($this->assignableRoles($request)))],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (isset($data['role'])) {
            $user->role_id = Role::idFor(RoleSlug::from($data['role']));
        }

        if (array_key_exists('is_active', $data)) {
            $user->is_active = (bool) $data['is_active'];

            if (! $user->is_active) {
                // Kill existing sessions immediately.
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }
        }

        $user->save();

        return back()->with('toast', ['type' => 'success', 'message' => 'Compte mis à jour.']);
    }

    public function destroy(User $user): RedirectResponse
    {
        if (Gate::denies('delete', $user)) {
            return back()->with('toast', ['type' => 'error', 'message' => 'Ce compte ne peut pas être supprimé (il possède des commandes ou des droits supérieurs). Désactivez-le plutôt.']);
        }

        DB::table('sessions')->where('user_id', $user->id)->delete();
        $user->delete();

        return redirect()->route('admin.users.index')->with('toast', ['type' => 'success', 'message' => 'Compte supprimé.']);
    }

    /** Only super admins can grant the super admin role. */
    private function assignableRoles(Request $request): array
    {
        return collect(RoleSlug::cases())
            ->reject(fn (RoleSlug $r) => $r === RoleSlug::SuperAdmin && ! $request->user()->isSuperAdmin())
            ->mapWithKeys(fn (RoleSlug $r) => [$r->value => $r->label()])->all();
    }
}
