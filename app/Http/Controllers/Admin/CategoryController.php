<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use App\Support\UniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Category::class);

        $categories = Category::query()
            ->withCount('products')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        $this->authorize('create', Category::class);

        return view('admin.categories.create');
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', Category::class);

        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? UniqueSlug::make(Category::class, $data['name'], null, 'category');
        $data['is_active'] = $request->boolean('is_active');

        Category::query()->create($data);

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'تم إنشاء التصنيف بنجاح.');
    }

    public function edit(Category $category): View
    {
        $this->authorize('update', $category);

        return view('admin.categories.edit', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $this->authorize('update', $category);

        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? $category->slug;
        $data['is_active'] = $request->boolean('is_active');

        $category->update($data);

        return redirect()
            ->route('admin.categories.edit', $category)
            ->with('status', 'تم تحديث التصنيف بنجاح.');
    }

    public function toggleStatus(Category $category): RedirectResponse
    {
        $this->authorize('update', $category);

        $category->update(['is_active' => ! $category->is_active]);

        return back()->with(
            'status',
            $category->is_active ? 'تم تفعيل التصنيف.' : 'تم تعطيل التصنيف.',
        );
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        if ($category->products()->withTrashed()->exists()) {
            return back()->with(
                'error',
                'لا يمكن حذف تصنيف مرتبط بمنتجات، بما في ذلك منتجات محذوفة قديمة. عطّل التصنيف أو انقل المنتجات إلى تصنيف آخر أولًا.',
            );
        }

        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'تم حذف التصنيف.');
    }
}
