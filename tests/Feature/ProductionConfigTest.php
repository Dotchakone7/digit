<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ProductionConfigTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TrustProxies::flushState();
        parent::tearDown();
    }

    public function test_https_is_detected_behind_a_trusted_proxy(): void
    {
        config(['shop.trusted_proxies' => '*']);
        $this->app->getProvider(AppServiceProvider::class)->boot();
        Route::get('/_scheme', fn () => request()->isSecure() ? 'https' : 'http')->middleware('web');

        $this->get('/_scheme', ['X-Forwarded-Proto' => 'https'])->assertSee('https');
    }

    public function test_robots_txt_allows_indexing_only_in_production(): void
    {
        $this->get('/robots.txt')->assertSee('Disallow: /');

        $this->app['env'] = 'production';
        $this->get('/robots.txt')->assertSee('Disallow: /admin')->assertSee('Sitemap:');
    }
}
