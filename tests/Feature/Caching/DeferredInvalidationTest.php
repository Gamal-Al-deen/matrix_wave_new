<?php

namespace Tests\Feature\Caching;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Support\PublicCache;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * Cache invalidation must follow the database transaction boundary.
 *
 * These tests intentionally avoid RefreshDatabase: its wrapping transaction
 * never reaches level 0, so Laravel would never execute after-commit callbacks
 * and the deferral could not be observed.
 */
class DeferredInvalidationTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::store('array')->flush();
    }

    public function test_a_write_outside_a_transaction_invalidates_immediately(): void
    {
        $category = $this->makeCategory('Phones', 'phones');

        $this->assertSame(0, $this->cachedProductCount($category));

        $this->makeProduct($category);

        $this->assertNull(Cache::get(PublicCache::ACTIVE_CATEGORIES));
        $this->assertSame(1, $this->cachedProductCount($category));
    }

    public function test_invalidation_inside_a_transaction_waits_for_the_commit(): void
    {
        $category = $this->makeCategory('Phones', 'phones');

        $this->assertSame(0, $this->cachedProductCount($category));

        DB::transaction(function () use ($category): void {
            $this->makeProduct($category);

            $this->assertIsArray(
                Cache::get(PublicCache::ACTIVE_CATEGORIES),
                'The cache must stay untouched until the transaction commits.',
            );
        });

        $this->assertNull(Cache::get(PublicCache::ACTIVE_CATEGORIES));
        $this->assertSame(1, $this->cachedProductCount($category));
    }

    public function test_a_rolled_back_transaction_does_not_invalidate_the_cache(): void
    {
        $category = $this->makeCategory('Phones', 'phones');
        $product = $this->makeProduct($category);

        $this->assertSame(1, $this->cachedProductCount($category));

        try {
            DB::transaction(function () use ($product): void {
                $product->update(['status' => 'inactive']);

                $this->assertIsArray(
                    Cache::get(PublicCache::ACTIVE_CATEGORIES),
                    'A rolled-back write must not drop the cache.',
                );

                throw new RuntimeException('Rollback on purpose.');
            });
        } catch (RuntimeException) {
            // Expected: the transaction must roll back.
        }

        $this->assertSame(ProductStatus::Available, $product->refresh()->status);
        $this->assertIsArray(Cache::get(PublicCache::ACTIVE_CATEGORIES));
        $this->assertSame(1, $this->cachedProductCount($category));
    }

    public function test_nested_transactions_only_invalidate_after_the_root_commit(): void
    {
        $category = $this->makeCategory('Phones', 'phones');

        $this->assertSame(0, $this->cachedProductCount($category));

        DB::transaction(function () use ($category): void {
            DB::transaction(function () use ($category): void {
                $this->makeProduct($category);
            });

            $this->assertIsArray(
                Cache::get(PublicCache::ACTIVE_CATEGORIES),
                'Releasing an inner savepoint must not flush the cache.',
            );
        });

        $this->assertNull(Cache::get(PublicCache::ACTIVE_CATEGORIES));
        $this->assertSame(1, $this->cachedProductCount($category));
    }

    public function test_a_site_settings_update_clears_the_request_memo_before_commit(): void
    {
        $settings = $this->makeSettings(['site_name' => 'Original']);

        $this->assertSame('Original', SiteSetting::current()?->site_name);

        DB::transaction(function () use ($settings): void {
            $settings->update(['site_name' => 'Changed']);

            $this->assertFalse(app()->bound(SiteSetting::CURRENT_CACHE_KEY));
            $this->assertIsArray(Cache::get(PublicCache::SITE_SETTINGS));
        });

        $this->assertNull(Cache::get(PublicCache::SITE_SETTINGS));
        $this->assertSame('Changed', SiteSetting::current()?->site_name);
    }

    public function test_a_deferred_force_delete_invalidates_after_the_commit(): void
    {
        $category = $this->makeCategory('Phones', 'phones');
        $product = $this->makeProduct($category);

        $this->assertSame(1, $this->cachedProductCount($category));

        DB::transaction(function () use ($product): void {
            $product->forceDelete();

            $this->assertIsArray(Cache::get(PublicCache::ACTIVE_CATEGORIES));
        });

        $this->assertNull(Cache::get(PublicCache::ACTIVE_CATEGORIES));
        $this->assertSame(0, $this->cachedProductCount($category));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeSettings(array $attributes = []): SiteSetting
    {
        $settings = new SiteSetting;
        $settings->fill(array_merge([
            'site_name' => 'Matrix Wave',
            'currency_code' => 'USD',
        ], $attributes));
        $settings->id = SiteSetting::SINGLETON_ID;
        $settings->save();

        return $settings;
    }

    private function makeCategory(string $name, string $slug): Category
    {
        return Category::query()->create([
            'name' => $name,
            'slug' => $slug,
            'description' => null,
            'is_active' => true,
        ]);
    }

    private function makeProduct(Category $category): Product
    {
        return Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Product '.uniqid(),
            'slug' => 'product-'.uniqid(),
            'description' => null,
            'price' => '10.00',
            'status' => 'available',
        ]);
    }

    private function cachedProductCount(Category $category): int
    {
        $cached = Category::publicWithProductCounts()->firstWhere('id', $category->id);

        $this->assertNotNull($cached, 'The category should exist in the cached list.');

        return (int) $cached->products_count;
    }
}
