<?php

namespace Tests\Feature\Caching;

use App\Enums\SocialPlatform;
use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\SocialLink;
use App\Support\PublicCache;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

class PublicDataCacheTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::store('array')->flush();
    }

    public function test_site_settings_are_cached_and_reused_without_database_queries(): void
    {
        $this->makeSettings(['site_name' => 'Matrix Wave']);

        $firstQueries = $this->countQueries(function (): void {
            $settings = SiteSetting::current();

            $this->assertNotNull($settings);
            $this->assertSame('Matrix Wave', $settings->site_name);
        });

        $this->assertGreaterThan(0, $firstQueries);
        $this->assertIsArray(Cache::get(PublicCache::SITE_SETTINGS));

        // Simulate a new request while keeping the persistent cache warm.
        app()->forgetInstance(SiteSetting::CURRENT_CACHE_KEY);

        $secondQueries = $this->countQueries(function (): void {
            $this->assertSame('Matrix Wave', SiteSetting::current()?->site_name);
        });

        $this->assertSame(0, $secondQueries);
    }

    public function test_updating_site_settings_invalidates_the_cached_copy(): void
    {
        $settings = $this->makeSettings(['site_name' => 'Old name']);

        SiteSetting::current();
        $this->assertIsArray(Cache::get(PublicCache::SITE_SETTINGS));

        $settings->update(['site_name' => 'New name']);

        $this->assertNull(Cache::get(PublicCache::SITE_SETTINGS));
        $this->assertSame('New name', SiteSetting::current()?->site_name);
    }

    public function test_deleting_site_settings_invalidates_the_cached_copy(): void
    {
        $settings = $this->makeSettings();

        $this->assertNotNull(SiteSetting::current());
        $this->assertIsArray(Cache::get(PublicCache::SITE_SETTINGS));

        $settings->delete();

        $this->assertNull(Cache::get(PublicCache::SITE_SETTINGS));
        $this->assertNull(SiteSetting::current());
    }

    public function test_current_or_create_repopulates_after_the_row_was_deleted(): void
    {
        $settings = $this->makeSettings(['site_name' => 'Temporary']);
        $settings->delete();

        $recreated = SiteSetting::currentOrCreate();

        $this->assertSame(SiteSetting::SINGLETON_ID, $recreated->id);
        $this->assertSame(SiteSetting::SINGLETON_ID, SiteSetting::current()?->id);
    }

    public function test_a_rolled_back_settings_write_does_not_leave_stale_cached_data(): void
    {
        $settings = $this->makeSettings(['site_name' => 'Original']);

        $this->assertSame('Original', SiteSetting::current()?->site_name);

        try {
            DB::transaction(function () use ($settings): void {
                $settings->update(['site_name' => 'Changed']);
                $settings->refresh();

                throw new RuntimeException('Rollback on purpose.');
            });
        } catch (RuntimeException) {
            // Expected: the transaction must roll back.
        }

        $this->assertSame('Original', SiteSetting::current()?->site_name);
    }

    public function test_expired_site_settings_cache_is_refreshed(): void
    {
        $this->makeSettings(['site_name' => 'Cached name']);

        SiteSetting::current();
        $this->travel(PublicCache::TTL_SITE_SETTINGS + 1)->seconds();
        app()->forgetInstance(SiteSetting::CURRENT_CACHE_KEY);

        $queries = $this->countQueries(function (): void {
            $this->assertSame('Cached name', SiteSetting::current()?->site_name);
        });

        $this->assertGreaterThan(0, $queries);
    }

    public function test_active_social_links_are_cached_in_footer_order(): void
    {
        $second = $this->makeSocialLink('https://example.com/second', 2);
        $first = $this->makeSocialLink('https://example.com/first', 1);
        $this->makeSocialLink('https://example.com/hidden', 3, false);

        $firstQueries = $this->countQueries(function () use ($second, $first): void {
            $links = SocialLink::activeLinks();

            $this->assertCount(2, $links);
            $this->assertSame([$first->id, $second->id], $links->pluck('id')->all());
            $this->assertInstanceOf(SocialPlatform::class, $links->first()->platform);
            $this->assertSame('facebook', $links->first()->platform->value);
        });

        $this->assertGreaterThan(0, $firstQueries);
        $this->assertIsArray(Cache::get(PublicCache::SOCIAL_LINKS));

        $secondQueries = $this->countQueries(function () use ($second, $first): void {
            $this->assertSame(
                [$first->id, $second->id],
                SocialLink::activeLinks()->pluck('id')->all(),
            );
        });

        $this->assertSame(0, $secondQueries);
    }

    public function test_social_link_changes_invalidate_the_cached_links(): void
    {
        $link = $this->makeSocialLink('https://example.com/original', 1);

        $this->assertSame('https://example.com/original', SocialLink::activeLinks()->first()?->url);

        $link->update(['url' => 'https://example.com/updated']);

        $this->assertNull(Cache::get(PublicCache::SOCIAL_LINKS));
        $this->assertSame('https://example.com/updated', SocialLink::activeLinks()->first()?->url);

        $link->update(['is_active' => false]);

        $this->assertNull(Cache::get(PublicCache::SOCIAL_LINKS));
        $this->assertCount(0, SocialLink::activeLinks());

        $link->update(['is_active' => true]);
        $this->assertCount(1, SocialLink::activeLinks());

        $link->delete();

        $this->assertNull(Cache::get(PublicCache::SOCIAL_LINKS));
        $this->assertCount(0, SocialLink::activeLinks());
    }

    public function test_active_categories_with_public_product_counts_are_cached(): void
    {
        $beta = $this->makeCategory('Beta', 'beta');
        $alpha = $this->makeCategory('Alpha', 'alpha');
        $this->makeCategory('Hidden', 'hidden', false);

        $this->makeProduct($beta, 'available');
        $this->makeProduct($beta, 'out_of_stock');
        $this->makeProduct($beta, 'inactive');
        $this->makeProduct($beta, 'available')->delete();

        $firstQueries = $this->countQueries(function () use ($beta, $alpha): void {
            $categories = Category::publicWithProductCounts();

            $this->assertSame([$alpha->id, $beta->id], $categories->pluck('id')->all());
            $this->assertSame(0, (int) $categories->firstWhere('id', $alpha->id)->products_count);
            $this->assertSame(2, (int) $categories->firstWhere('id', $beta->id)->products_count);
        });

        $this->assertGreaterThan(0, $firstQueries);
        $this->assertIsArray(Cache::get(PublicCache::ACTIVE_CATEGORIES));

        $secondQueries = $this->countQueries(function () use ($beta, $alpha): void {
            $categories = Category::publicWithProductCounts();

            $this->assertSame([$alpha->id, $beta->id], $categories->pluck('id')->all());
        });

        $this->assertSame(0, $secondQueries);
    }

    public function test_cached_categories_match_an_uncached_database_query(): void
    {
        $category = $this->makeCategory('Phones', 'phones');
        $this->makeProduct($category, 'available');
        $this->makeProduct($category, 'out_of_stock');
        $this->makeProduct($category, 'inactive');

        $cached = Category::publicWithProductCounts();

        $direct = Category::query()
            ->active()
            ->withCount([
                'products' => fn ($query) => $query->publiclyVisible(),
            ])
            ->orderBy('name')
            ->get();

        $this->assertSame($direct->pluck('id')->all(), $cached->pluck('id')->all());
        $this->assertSame(
            $direct->map(fn (Category $item): int => (int) $item->products_count)->all(),
            $cached->map(fn (Category $item): int => (int) $item->products_count)->all(),
        );
    }

    public function test_product_writes_invalidate_cached_category_counts(): void
    {
        $category = $this->makeCategory('Phones', 'phones');
        $product = $this->makeProduct($category, 'available');

        $this->assertSame(1, $this->cachedProductCount($category));

        $second = $this->makeProduct($category, 'available');
        $this->assertSame(2, $this->cachedProductCount($category));

        $product->update(['status' => 'inactive']);
        $this->assertSame(1, $this->cachedProductCount($category));

        $product->update(['status' => 'out_of_stock']);
        $this->assertSame(2, $this->cachedProductCount($category));

        $second->delete();
        $this->assertSame(1, $this->cachedProductCount($category));

        $second->restore();
        $this->assertSame(2, $this->cachedProductCount($category));
    }

    public function test_permanent_product_deletion_invalidates_cached_category_counts(): void
    {
        $category = $this->makeCategory('Phones', 'phones');
        $product = $this->makeProduct($category, 'available');

        $this->assertSame(1, $this->cachedProductCount($category));

        $product->forceDelete();

        $this->assertNull(Cache::get(PublicCache::ACTIVE_CATEGORIES));
        $this->assertSame(0, $this->cachedProductCount($category));
    }

    public function test_category_writes_invalidate_the_cached_list(): void
    {
        $alpha = $this->makeCategory('Alpha', 'alpha');
        $beta = $this->makeCategory('Beta', 'beta');

        $this->assertSame(['Alpha', 'Beta'], Category::publicWithProductCounts()->pluck('name')->all());

        $beta->update(['name' => 'Aardvark']);

        $this->assertNull(Cache::get(PublicCache::ACTIVE_CATEGORIES));
        $this->assertSame(['Aardvark', 'Alpha'], Category::publicWithProductCounts()->pluck('name')->all());

        $alpha->update(['is_active' => false]);

        $this->assertSame(['Aardvark'], Category::publicWithProductCounts()->pluck('name')->all());

        $gamma = $this->makeCategory('Gamma', 'gamma');

        $this->assertSame(['Aardvark', 'Gamma'], Category::publicWithProductCounts()->pluck('name')->all());

        $gamma->delete();

        $this->assertSame(['Aardvark'], Category::publicWithProductCounts()->pluck('name')->all());
    }

    public function test_expired_category_cache_is_refreshed(): void
    {
        $category = $this->makeCategory('Phones', 'phones');

        $this->assertSame(0, $this->cachedProductCount($category));

        $this->travel(PublicCache::TTL_ACTIVE_CATEGORIES + 1)->seconds();

        $queries = $this->countQueries(function () use ($category): void {
            $this->assertSame(0, $this->cachedProductCount($category));
        });

        $this->assertGreaterThan(0, $queries);
    }

    public function test_a_cache_read_failure_falls_back_to_the_database(): void
    {
        Log::spy();

        Cache::shouldReceive('get')->once()->andThrow(new RuntimeException('Cache read failed.'));

        $result = PublicCache::remember('public:test:v1', 60, fn (): array => ['value' => 42]);

        $this->assertSame(['value' => 42], $result);
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_a_cache_write_failure_still_returns_the_computed_value(): void
    {
        Log::spy();

        Cache::shouldReceive('get')->once()->andReturn(null);
        Cache::shouldReceive('put')->once()->andThrow(new RuntimeException('Cache write failed.'));

        $result = PublicCache::remember('public:test:v1', 60, fn (): array => ['value' => 42]);

        $this->assertSame(['value' => 42], $result);
        Log::shouldHaveReceived('warning')->once();
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

    private function makeSocialLink(string $url, int $sortOrder, bool $active = true): SocialLink
    {
        return SocialLink::query()->create([
            'platform' => 'facebook',
            'url' => $url,
            'icon_key' => 'facebook',
            'is_active' => $active,
            'sort_order' => $sortOrder,
        ]);
    }

    private function makeCategory(string $name, string $slug, bool $active = true): Category
    {
        return Category::query()->create([
            'name' => $name,
            'slug' => $slug,
            'description' => null,
            'is_active' => $active,
        ]);
    }

    private function makeProduct(Category $category, string $status): Product
    {
        return Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Product '.uniqid(),
            'slug' => 'product-'.uniqid(),
            'description' => null,
            'price' => '10.00',
            'status' => $status,
        ]);
    }

    private function cachedProductCount(Category $category): int
    {
        $cached = Category::publicWithProductCounts()->firstWhere('id', $category->id);

        $this->assertNotNull($cached, 'The category should exist in the cached list.');

        return (int) $cached->products_count;
    }

    private function countQueries(\Closure $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $callback();

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }
}
