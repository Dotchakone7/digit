<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $xml = Cache::remember('seo.sitemap', now()->addHour(), fn () => view('seo.sitemap', [
            'products' => Product::query()->published()->select(['slug', 'updated_at'])->latest('updated_at')->get(),
            'categories' => Category::query()->active()->select(['slug', 'updated_at'])->get(),
            'pages' => array_keys(PageController::PAGES),
        ])->render());

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $lines = app()->isProduction()
            ? ['User-agent: *', 'Disallow: /admin', 'Disallow: /compte', 'Disallow: /panier', 'Disallow: /commande', 'Disallow: /paiements', 'Disallow: /recherche', '', 'Sitemap: '.route('sitemap')]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
