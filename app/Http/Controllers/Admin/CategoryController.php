<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\ImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private readonly ImageService $images) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Category::class);

        return view('admin.categories.index', [
            'categories' => Category::query()->roots()->ordered()
                ->with(['children' => fn ($q) => $q->ordered()->withCount('products')])
                ->withCount('products')->get(),
        ]);
    }

    public function create(): View
    {
        return $this->form(new Category(['is_active' => true, 'position' => 0]));
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Category::class);

        $category = new Category;
        $this->save($request, $category);

        return redirect()->route('admin.categories.index')->with('toast', ['type' => 'success', 'message' => 'Catégorie créée.']);
    }

    public function edit(Category $category): View
    {
        return $this->form($category);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        Gate::authorize('update', $category);

        $this->save($request, $category);

        return redirect()->route('admin.categories.index')->with('toast', ['type' => 'success', 'message' => 'Catégorie enregistrée.']);
    }

    public function destroy(Category $category): RedirectResponse
    {
        Gate::authorize('delete', $category);

        if ($category->products()->exists() || $category->children()->exists()) {
            return back()->with('toast', ['type' => 'error', 'message' => 'Cette catégorie contient des produits ou des sous-catégories : déplacez-les ou désactivez-la plutôt.']);
        }

        $this->images->delete($category->image_path);
        $category->delete();

        return back()->with('toast', ['type' => 'success', 'message' => 'Catégorie supprimée.']);
    }

    private function save(Request $request, Category $category): void
    {
        $request->merge(['slug' => Str::slug($request->input('slug') ?: $request->input('name', ''))]);
        $maxKb = (int) config('shop.uploads.max_kb');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'alpha_dash', 'max:140', Rule::unique('categories', 'slug')->ignore($category->id)],
            // Two levels max; a category cannot be its own parent.
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->whereNull('parent_id'), Rule::notIn(array_filter([$category->id]))],
            'description' => ['nullable', 'string', 'max:2000'],
            'position' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', "max:{$maxKb}"],
            'remove_image' => ['nullable', 'boolean'],
        ], [], ['parent_id' => 'catégorie parente']);

        if ($category->exists && $category->children()->exists() && filled($data['parent_id'] ?? null)) {
            throw ValidationException::withMessages(['parent_id' => 'Une catégorie qui a des sous-catégories ne peut pas devenir une sous-catégorie.']);
        }

        if ($request->hasFile('image')) {
            $this->images->delete($category->image_path);
            $data['image_path'] = $this->images->store($request->file('image'), 'categories');
        } elseif ($request->boolean('remove_image')) {
            $this->images->delete($category->image_path);
            $data['image_path'] = null;
        }

        $category->fill(collect($data)->except(['image', 'remove_image'])->all() + [
            'is_active' => $request->boolean('is_active'),
            'position' => (int) ($data['position'] ?? 0),
        ])->save();
    }

    private function form(Category $category): View
    {
        Gate::authorize($category->exists ? 'update' : 'create', $category->exists ? $category : Category::class);

        return view('admin.categories.form', [
            'category' => $category,
            'parents' => Category::query()->roots()->whereKeyNot($category->id ?? 0)->ordered()->pluck('name', 'id'),
        ]);
    }
}
