<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\Storage\ImageStorageService;
use App\Support\UniqueSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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

        // Re-render images that were uploaded before a failed publish attempt so
        // the administrator keeps them and can retry without re-selecting.
        $draftImages = (array) session(ProductImageController::DRAFT_SESSION_KEY, []);

        return view('admin.products.create', compact('categories', 'draftImages'));
    }

    public function store(ProductRequest $request, ImageStorageService $storage): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $data = $request->validated();
        unset($data['draft_images']);
        $data['slug'] = $data['slug'] ?? UniqueSlug::make(Product::class, $data['name'], null, 'product');

        // Resolve the images uploaded automatically before publishing. Only the
        // keys submitted with the form are used, so removed drafts are ignored.
        $sessionDrafts = (array) $request->session()->get(ProductImageController::DRAFT_SESSION_KEY, []);
        $selectedDrafts = [];

        foreach ((array) $request->input('draft_images', []) as $key) {
            if (is_string($key) && isset($sessionDrafts[$key])) {
                $selectedDrafts[$key] = $sessionDrafts[$key];
            }
        }

        if ($selectedDrafts === []) {
            throw ValidationException::withMessages([
                'draft_images' => 'يجب رفع صورة واحدة على الأقل قبل نشر المنتج.',
            ]);
        }

        try {
            $product = DB::transaction(function () use ($data, $selectedDrafts): Product {
                $product = Product::query()->create($data);

                $sortOrder = 0;

                foreach ($selectedDrafts as $draft) {
                    $product->images()->create([
                        'storage_path' => $draft['storage_path'],
                        'public_url' => $draft['public_url'],
                        'original_name' => $draft['original_name'],
                        'mime_type' => $draft['mime_type'],
                        'size_bytes' => $draft['size_bytes'],
                        'sort_order' => ++$sortOrder,
                        'alt_text' => $product->name,
                    ]);
                }

                return $product;
            });
        } catch (\Throwable $exception) {
            // The product was not created, so the freshly stored files are not
            // referenced anywhere. Remove them instead of leaving orphans, but
            // keep the usable drafts so the administrator can retry.
            foreach ($selectedDrafts as $key => $draft) {
                $storage->delete($draft['storage_path'] ?? null);
                unset($sessionDrafts[$key]);
            }

            $request->session()->put(ProductImageController::DRAFT_SESSION_KEY, $sessionDrafts);

            throw $exception;
        }

        // Any draft the administrator uploaded but did not publish with the
        // product is no longer needed: remove its file and clear the session.
        foreach ($sessionDrafts as $key => $draft) {
            if (! isset($selectedDrafts[$key])) {
                $storage->delete($draft['storage_path'] ?? null);
            }
        }

        $request->session()->forget(ProductImageController::DRAFT_SESSION_KEY);

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'تم إضافة المنتج بنجاح');
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
