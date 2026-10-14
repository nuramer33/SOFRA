<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\CategoryRequest;
use App\Models\Category;
use App\Support\ImageStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Every query here is limited to the owner's shop by the tenant scope,
 * including route model binding: another shop's {category} is a 404.
 */
class CategoryController extends Controller
{
    public function __construct(private ImageStore $images) {}

    public function index(): View
    {
        return view('dashboard.categories.index', [
            'categories' => Category::query()
                ->withCount('products')
                ->orderBy('sort_order')->orderBy('id')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('dashboard.categories.form', ['category' => new Category(['is_active' => true])]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $category = new Category(['is_active' => $request->boolean('is_active')]);
        $this->fill($category, $request);
        $category->save();

        return redirect()->route('categories.index')
            ->with('status', __('app.categories.created'));
    }

    public function edit(Category $category): View
    {
        return view('dashboard.categories.form', ['category' => $category->loadCount('products')]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->is_active = $request->boolean('is_active');
        $this->fill($category, $request);
        $category->save();

        return redirect()->route('categories.index')
            ->with('status', __('app.categories.updated'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete(); // also deletes its products and their images

        return redirect()->route('categories.index')
            ->with('status', __('app.categories.deleted'));
    }

    private function fill(Category $category, CategoryRequest $request): void
    {
        $locales = $request->shop()->contentLocales();
        $category->replaceTranslations('name', $request->translations('name', $locales));

        if ($request->hasFile('image') || $request->boolean('remove_image')) {
            $this->images->delete($category->image_path);
            $category->image_path = $request->hasFile('image')
                ? $this->images->store($request->file('image'), $request->shop(), 'category')
                : null;
        }
    }
}
