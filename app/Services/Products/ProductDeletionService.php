<?php

namespace App\Services\Products;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Permanently deletes a product while preserving historical sales records.
 *
 * Database rows and storage objects cannot share a transaction, so the
 * database is committed first and exclusively-owned image objects are removed
 * afterwards. A storage failure therefore leaves an orphan object, never a
 * database row pointing to a missing object.
 */
class ProductDeletionService
{
    public function __construct(
        private readonly ProductImageStorageCleaner $imageCleaner,
    ) {}

    /**
     * Delete the product and its transient dependents.
     *
     * @return int Number of image objects that could not be removed from storage.
     */
    public function delete(Product $product): int
    {
        $imagePaths = DB::table('product_images')
            ->where('product_id', $product->getKey())
            ->pluck('storage_path')
            ->filter(fn (mixed $path): bool => is_string($path) && trim($path) !== '')
            ->unique()
            ->values()
            ->all();

        DB::transaction(function () use ($product): void {
            // Order items are historical records. Detach the product reference
            // but keep the snapshot, prices, quantities, and totals untouched.
            DB::table('order_items')
                ->where('product_id', $product->getKey())
                ->update(['product_id' => null]);

            // Cart items are transient references and are protected by a
            // restrictOnDelete constraint. Removing only these rows keeps the
            // hard delete possible without deleting other users' carts.
            DB::table('cart_items')
                ->where('product_id', $product->getKey())
                ->delete();

            DB::table('product_images')
                ->where('product_id', $product->getKey())
                ->delete();

            $product->forceDelete();
        });

        $failures = 0;

        foreach ($imagePaths as $path) {
            if (! $this->imageCleaner->deleteIfUnreferenced($path)) {
                $failures++;
            }
        }

        if ($failures > 0) {
            Log::warning('Product deleted but some image objects could not be removed from storage.', [
                'product_id' => (string) $product->getKey(),
                'failed_objects' => $failures,
            ]);
        }

        return $failures;
    }
}
