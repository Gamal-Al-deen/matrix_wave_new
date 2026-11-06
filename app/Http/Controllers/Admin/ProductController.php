<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Support\UniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $showTrashed = $request->boolean('trashed');

        $products = Product::query()
            ->with(['category', 'images'])
            ->withCount('images')
            ->when($showTrashed, fn ($query) => $query->onlyTrashed())
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.products.index', compact('products', 'showTrashed'));
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        $categories = Category::query()->orderBy('name')->get();

        return view('admin.products.create', compact('categories'));
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? UniqueSlug::make(Product::class, $data['name'], null, 'product');

        $product = Product::query()->create($data);

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('status', 'تم إنشاء المنتج. يمكنك الآن إضافة الصور.');
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        $product->load(['images', 'category']);
        $categories = Category::query()->orderBy('name')->get();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? $product->slug;

        $product->update($data);

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('status', 'تم تحديث المنتج بنجاح.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'تم حذف المنتج (حذف آمن مع الحفاظ على سجلات الطلبات).');
    }

    public function restore(string $product): RedirectResponse
    {
        $restored = Product::withTrashed()->findOrFail($product);

        $this->authorize('restore', $restored);

        $restored->restore();

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'تمت استعادة المنتج.');
    }
}
