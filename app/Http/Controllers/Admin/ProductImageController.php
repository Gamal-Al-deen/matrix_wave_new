<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductImageReplaceRequest;
use App\Http\Requests\Admin\ProductImagesRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\Products\ProductImageStorageCleaner;
use App\Services\Storage\ImageStorageService;
use App\Support\ImageRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProductImageController extends Controller
{
    /**
     * Session key holding images uploaded while creating a product that does
     * not exist yet. The listing is cleared once the product is published.
     */
    public const DRAFT_SESSION_KEY = 'admin_product_draft_images';

    /**
     * Upload a single image while the product is still being created.
     *
     * The image is written to the storage disk immediately and its metadata is
     * kept in the session so the create form can publish it atomically later.
     */
    public function storeDraft(Request $request, ImageStorageService $storage): JsonResponse
    {
        $this->authorize('create', Product::class);

        $request->validate([
            'image' => ImageRules::make(),
        ], [], ['image' => 'الصورة']);

        $drafts = (array) $request->session()->get(self::DRAFT_SESSION_KEY, []);
        $limit = (int) config('media.max_product_images', 10);

        if (count($drafts) >= $limit) {
            throw ValidationException::withMessages([
                'image' => "لا يمكن أن يتجاوز عدد صور المنتج {$limit} صورة.",
            ]);
        }

        $stored = $storage->store($request->file('image'), $storage->folder('product', 'draft'));

        $key = (string) Str::uuid();
        $drafts[$key] = $stored->toArray();
        $request->session()->put(self::DRAFT_SESSION_KEY, $drafts);

        return response()->json([
            'success' => true,
            'key' => $key,
            'url' => $stored->publicUrl,
            'name' => $stored->originalName,
            'message' => 'تم رفع الصورة بنجاح.',
        ]);
    }

    /**
     * Remove an image uploaded during product creation before publishing.
     */
    public function destroyDraft(Request $request, string $key, ImageStorageService $storage): JsonResponse
    {
        $this->authorize('create', Product::class);

        $drafts = (array) $request->session()->get(self::DRAFT_SESSION_KEY, []);

        if (! isset($drafts[$key])) {
            return response()->json(['success' => true]);
        }

        $path = $drafts[$key]['storage_path'] ?? null;

        // Keep the draft tracked when its object cannot be removed, so the
        // administrator can retry instead of leaving an untracked orphan file.
        if (! $storage->delete($path)) {
            return response()->json([
                'success' => false,
                'message' => 'تعذر حذف ملف الصورة من التخزين. حاول مرة أخرى.',
            ], 500);
        }

        unset($drafts[$key]);
        $request->session()->put(self::DRAFT_SESSION_KEY, $drafts);

        return response()->json(['success' => true]);
    }

    public function store(
        ProductImagesRequest $request,
        Product $product,
        ImageStorageService $storage,
    ): RedirectResponse {
        $this->authorize('update', $product);

        $files = $request->file('images', []);
        $currentCount = $product->images()->count();
        $limit = (int) config('media.max_product_images', 10);

        if ($currentCount + count($files) > $limit) {
            throw ValidationException::withMessages([
                'images' => "لا يمكن أن يتجاوز عدد صور المنتج {$limit} صورة.",
            ]);
        }

        $sortOrder = (int) $product->images()->max('sort_order');
        $folder = $storage->folder('product', $product->id);
        $storedCount = 0;

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $stored = $storage->store($file, $folder);

            try {
                $product->images()->create([
                    ...$stored->toArray(),
                    'sort_order' => ++$sortOrder,
                    'alt_text' => $product->name,
                ]);
            } catch (Throwable $exception) {
                // Remove the new object only when the database confirms that no
                // row references it. If the check fails, preserve the object
                // because the database state cannot be determined.
                if (! $this->storedPathIsReferenced($stored->path)) {
                    $storage->delete($stored->path);
                }

                throw $exception;
            }

            $storedCount++;
        }

        return back()->with('status', "تم رفع {$storedCount} صورة بنجاح.");
    }

    public function replace(
        ProductImageReplaceRequest $request,
        Product $product,
        ProductImage $image,
        ImageStorageService $storage,
        ProductImageStorageCleaner $cleaner,
    ): RedirectResponse {
        $this->authorize('update', $product);
        $this->ensureImageBelongsToProduct($product, $image);

        $file = $request->file('image');
        $previousPath = $image->storage_path;

        $stored = $storage->store(
            $file,
            $storage->folder('product', $product->id),
        );

        try {
            $image->update([
                ...$stored->toArray(),
                'alt_text' => $image->alt_text ?: $product->name,
            ]);
        } catch (Throwable $exception) {
            // Only delete the new object when the database is known not to
            // reference it. If the lookup cannot be completed, preserve the
            // new object rather than risk deleting a referenced file.
            $currentPath = null;
            $lookupSucceeded = false;

            try {
                $currentPath = ProductImage::query()
                    ->whereKey($image->getKey())
                    ->value('storage_path');
                $lookupSucceeded = true;
            } catch (Throwable) {
                // Preserve the new object; the original failure is rethrown.
            }

            if ($lookupSucceeded) {
                if ($currentPath === $stored->path) {
                    $cleaner->deleteIfUnreferenced($previousPath);
                } else {
                    $storage->delete($stored->path);
                }
            }

            throw $exception;
        }

        if ($previousPath !== null && $previousPath !== $stored->path) {
            $cleaner->deleteIfUnreferenced($previousPath);
        }

        return back()->with('status', 'تم استبدال الصورة بنجاح.');
    }

    public function destroy(
        Product $product,
        ProductImage $image,
        ProductImageStorageCleaner $cleaner,
    ): RedirectResponse {
        $this->authorize('update', $product);
        $this->ensureImageBelongsToProduct($product, $image);

        $path = $image->storage_path;

        // Delete the target row first so it cannot outlive its file. The
        // cleaner then removes the object only when nothing else references it.
        $image->delete();

        if (! $cleaner->deleteIfUnreferenced($path)) {
            return back()->with(
                'error',
                'تم حذف بيانات الصورة نهائيًا، لكن تعذر حذف ملفها من التخزين. راجع سجلات النظام.',
            );
        }

        return back()->with('status', 'تم حذف الصورة.');
    }

    public function move(
        Request $request,
        Product $product,
        ProductImage $image,
    ): RedirectResponse {
        $this->authorize('update', $product);
        $this->ensureImageBelongsToProduct($product, $image);

        $direction = $request->validate([
            'direction' => ['required', 'in:up,down'],
        ])['direction'];

        $neighbor = $product->images()
            ->when(
                $direction === 'up',
                fn ($query) => $query->where('sort_order', '<', $image->sort_order)
                    ->orderByDesc('sort_order'),
                fn ($query) => $query->where('sort_order', '>', $image->sort_order)
                    ->orderBy('sort_order'),
            )
            ->first();

        if ($neighbor === null) {
            return back();
        }

        $currentOrder = $image->sort_order;

        $image->update(['sort_order' => $neighbor->sort_order]);
        $neighbor->update(['sort_order' => $currentOrder]);

        return back()->with('status', 'تم تحديث ترتيب الصور.');
    }

    private function ensureImageBelongsToProduct(Product $product, ProductImage $image): void
    {
        abort_unless($image->product_id === $product->id, 404);
    }

    /**
     * Treat an unreadable database state as "referenced" so the caller
     * preserves a newly uploaded object rather than risking data loss.
     */
    private function storedPathIsReferenced(string $path): bool
    {
        try {
            return ProductImage::query()
                ->where('storage_path', $path)
                ->exists();
        } catch (Throwable) {
            return true;
        }
    }
}
