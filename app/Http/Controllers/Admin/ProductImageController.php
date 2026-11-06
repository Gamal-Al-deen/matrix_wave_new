<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductImageReplaceRequest;
use App\Http\Requests\Admin\ProductImagesRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\Storage\ImageStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class ProductImageController extends Controller
{
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

            $product->images()->create([
                ...$stored->toArray(),
                'sort_order' => ++$sortOrder,
                'alt_text' => $product->name,
            ]);

            $storedCount++;
        }

        return back()->with('status', "تم رفع {$storedCount} صورة بنجاح.");
    }

    public function replace(
        ProductImageReplaceRequest $request,
        Product $product,
        ProductImage $image,
        ImageStorageService $storage,
    ): RedirectResponse {
        $this->authorize('update', $product);
        $this->ensureImageBelongsToProduct($product, $image);

        $file = $request->file('image');

        $stored = $storage->replace(
            $file,
            $storage->folder('product', $product->id),
            $image->storage_path,
        );

        $image->update([
            ...$stored->toArray(),
            'alt_text' => $image->alt_text ?: $product->name,
        ]);

        return back()->with('status', 'تم استبدال الصورة بنجاح.');
    }

    public function destroy(
        Product $product,
        ProductImage $image,
        ImageStorageService $storage,
    ): RedirectResponse {
        $this->authorize('update', $product);
        $this->ensureImageBelongsToProduct($product, $image);

        if (! $storage->delete($image->storage_path)) {
            return back()->with(
                'error',
                'تعذر حذف ملف الصورة من التخزين، لذلك أُبقيت بياناتها. حاول مرة أخرى.',
            );
        }

        $image->delete();

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
}
