<?php

namespace App\Services\Products;

use App\Services\Storage\ImageStorageService;
use Illuminate\Support\Facades\DB;

/**
 * Removes product image objects from storage only when no database record
 * still references the exact path.
 *
 * Product image paths are generated as UUID object keys, but the reference
 * check protects against accidental sharing with banners, wallet icons, the
 * site logo, or another product image row.
 */
class ProductImageStorageCleaner
{
    public function __construct(
        private readonly ImageStorageService $storage,
    ) {}

    /**
     * Delete the object unless another trusted database row references it.
     *
     * Returns true when the path is absent, shared, or deleted successfully.
     * Returns false only when an unshared object could not be deleted.
     */
    public function deleteIfUnreferenced(?string $path): bool
    {
        if ($path === null || trim($path) === '') {
            return true;
        }

        if ($this->isReferenced($path)) {
            return true;
        }

        return $this->storage->delete($path);
    }

    /**
     * Determine whether a trusted database column still points at this object.
     */
    public function isReferenced(string $path): bool
    {
        return DB::table('product_images')->where('storage_path', $path)->exists()
            || DB::table('banners')->where('image_storage_path', $path)->exists()
            || DB::table('wallets')->where('icon_storage_path', $path)->exists()
            || DB::table('site_settings')->where('logo_storage_path', $path)->exists();
    }
}
