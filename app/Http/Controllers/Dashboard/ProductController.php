<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Support\ImageStore;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Every query here is limited to the owner's shop by the tenant scope,
 * including route model binding: another shop's {product} is a 404.
 */
class ProductController extends Controller
{
    public function __construct(private ImageStore $images) {}

    public function index(): View
    {
        $categories = Category::query()
            ->with(['products' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])
            ->orderBy('sort_order')->orderBy('id')
            ->get();

        return view('dashboard.products.index', ['categories' => $categories]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $categories = $this->categoryOptions();

        if ($categories->isEmpty()) {
            return redirect()->route('categories.create')
                ->with('status', __('app.products.need_category'));
        }

        $product = new Product([
            'is_available' => true,
            'category_id' => $categories->firstWhere('id', $request->integer('category'))?->id ?? $categories->first()->id,
        ]);

        return view('dashboard.products.form', ['product' => $product, 'categories' => $categories]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = new Product(['category_id' => $request->integer('category_id')]);
        $this->fill($product, $request);
        $product->save();

        return redirect()->to(route('products.index').'#product-'.$product->id)
            ->with('status', __('app.products.created'));
    }

    public function edit(Product $product): View
    {
        return view('dashboard.products.form', ['product' => $product, 'categories' => $this->categoryOptions()]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $product->moveToCategory($request->integer('category_id'));
        $this->fill($product, $request);
        $product->save();

        return redirect()->to(route('products.index').'#product-'.$product->id)
            ->with('status', __('app.products.updated'));
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete(); // the model also deletes its image files

        return redirect()->route('products.index')
            ->with('status', __('app.products.deleted'));
    }

    private function fill(Product $product, ProductRequest $request): void
    {
        $locales = $request->shop()->contentLocales();

        $product->replaceTranslations('name', $request->translations('name', $locales));
        $product->replaceTranslations('description', $request->translations('description', $locales));
        $product->price = $request->validated('price');
        $product->compare_at_price = $request->validated('compare_at_price');
        $product->is_available = $request->boolean('is_available');
        $product->is_featured = $request->boolean('is_featured');

        if ($request->hasFile('image') || $request->boolean('remove_image')) {
            $this->images->delete($product->image_path);
            $product->image_path = $request->hasFile('image')
                ? $this->images->store($request->file('image'), $request->shop(), 'product')
                : null;
        }
    }

    /**
     * @return Collection<int, Category>
     */
    private function categoryOptions(): Collection
    {
        return Category::query()->orderBy('sort_order')->orderBy('id')->get(['id', 'name', 'shop_id']);
    }
}
