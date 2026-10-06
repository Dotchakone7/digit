<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Page shell + SEO; filtering itself is done live by App\Livewire\Shop\Catalog. */
class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        return $this->render($request);
    }

    public function category(Request $request, Category $category): View
    {
        abort_unless($category->is_active, 404);

        return $this->render($request, $category);
    }

    private function render(Request $request, ?Category $category = null): View
    {
        $q = is_string($request->query('q')) ? mb_substr(trim($request->query('q')), 0, 100) : null;

        return view('shop.catalog', [
            'category' => $category,
            'q' => $q,
            // Search results and deep pages are not worth indexing.
            'noindex' => filled($q) || $request->integer('page') > 1,
        ]);
    }
}
