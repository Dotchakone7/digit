<?php

namespace App\Http\Controllers\Shop;

use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $base = fn () => Product::query()->published()->forCard();

        $popular = $base()->orderByDesc('sales_count')->limit(8)->get();
        $newest = $base()->latest('published_at')->latest('id')->limit(8)->get();
        $deals = $base()->onSale()->orderByDesc('sales_count')->limit(4)->get();

        // Product counts include sub-categories.
        $counts = Product::query()->published()->selectRaw('category_id, COUNT(*) as total')
            ->groupBy('category_id')->pluck('total', 'category_id');
        $categories = Category::query()->active()->roots()->ordered()->with('children:id,parent_id')->limit(6)->get()
            ->each(fn (Category $c) => $c->products_count = $c->children->pluck('id')->push($c->id)->sum(fn ($id) => $counts[$id] ?? 0));

        return view('shop.home', [
            'categories' => $categories,
            'popular' => $popular,
            'newest' => $newest,
            'deals' => $deals,
            'heroProduct' => $base()->where('is_featured', true)->orderByDesc('sales_count')->first() ?? $popular->first(),
            'testimonials' => Review::query()->where('status', ReviewStatus::Approved)->where('rating', '>=', 4)
                ->with(['user:id,name', 'product:id,name,slug'])->latest()->limit(3)->get(),
        ]);
    }
}
