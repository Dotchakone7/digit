<?php

namespace App\Providers;

use App\Assistant\AssistantManager;
use App\Delivery\CourierManager;
use App\Models\Category;
use App\Models\User;
use App\Payments\PaymentManager;
use App\Services\Cart\CartService;
use App\Services\SettingsService;
use App\Services\WishlistService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentManager::class);
        $this->app->singleton(CourierManager::class);
        $this->app->singleton(AssistantManager::class);
        $this->app->scoped(SettingsService::class);
        $this->app->scoped(CartService::class);
        $this->app->scoped(WishlistService::class);
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction());
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
        Paginator::defaultView('components.pagination');

        $this->registerSqliteFunctions();
        $this->registerAbilities();
        $this->localizeAuthMails();
        $this->registerRateLimiters();
        $this->shareLayoutData();
    }

    /** RBAC: abilities from config/permissions.php, super admins can do everything. */
    private function registerAbilities(): void
    {
        Gate::before(fn (User $user) => $user->is_active && $user->isSuperAdmin() ? true : null);

        foreach (array_keys(config('permissions.abilities')) as $ability) {
            Gate::define($ability, fn (User $user) => $user->is_active && in_array($ability, $user->abilities(), true));
        }
    }

    /** PostgreSQL uses translate(); SQLite (tests, quick local setup) gets an equivalent function. */
    private function registerSqliteFunctions(): void
    {
        Event::listen(ConnectionEstablished::class, function (ConnectionEstablished $event) {
            if ($event->connection->getDriverName() === 'sqlite') {
                $event->connection->getPdo()->sqliteCreateFunction(
                    'shop_normalize', fn (?string $value) => $value === null ? null : Str::lower(Str::ascii($value)), 1
                );
            }
        });
    }

    private function localizeAuthMails(): void
    {
        ResetPassword::toMailUsing(fn (User $user, string $token) => (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe — '.config('shop.name'))
            ->greeting('Bonjour '.explode(' ', $user->name)[0].',')
            ->line('Vous avez demandé la réinitialisation du mot de passe de votre compte.')
            ->action('Choisir un nouveau mot de passe', route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->line('Ce lien expire dans '.config('auth.passwords.users.expire').' minutes.')
            ->line('Si vous n’êtes pas à l’origine de cette demande, ignorez simplement cet e-mail.')
            ->salutation('L’équipe '.config('shop.name')));
    }

    private function registerRateLimiters(): void
    {
        RateLimiter::for('auth', fn (Request $request) => [
            Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(20)->by($request->ip()),
        ]);
        RateLimiter::for('cart', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('search', fn (Request $request) => Limit::perMinute(90)->by($request->ip()));
        RateLimiter::for('checkout', fn (Request $request) => Limit::perMinute(10)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('webhooks', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
        RateLimiter::for('forms', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
    }

    private function shareLayoutData(): void
    {
        View::composer('layouts.shop', function ($view) {
            // Plain arrays only: Laravel refuses to unserialize objects from the cache.
            $view->with('navCategories', collect(Cache::remember('nav.categories', now()->addMinutes(10), fn () => Category::query()
                ->active()->roots()->ordered()
                ->with(['children' => fn ($q) => $q->active()->ordered()])
                ->get(['id', 'parent_id', 'name', 'slug', 'image_path'])
                ->map(fn (Category $c) => [
                    'name' => $c->name,
                    'slug' => $c->slug,
                    'image_url' => $c->image_url,
                    'children' => $c->children->map(fn (Category $child) => ['name' => $child->name, 'slug' => $child->slug])->all(),
                ])->all())));
            $view->with('cartCount', app(CartService::class)->count());
        });
    }
}
