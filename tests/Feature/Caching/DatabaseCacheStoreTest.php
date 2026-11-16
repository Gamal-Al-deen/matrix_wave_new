<?php

namespace Tests\Feature\Caching;

use App\Enums\SocialPlatform;
use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\SocialLink;
use App\Support\PublicCache;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Covers Laravel's database cache store against the isolated in-memory SQLite
 * database that the test suite always uses.
 */
class DatabaseCacheStoreTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        // Refuse to run against anything except the disposable test database.
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        config(['cache.default' => 'database']);

        Cache::store('database')->flush();
    }

    public function test_values_can_be_written_and_read_from_the_database_store(): void
    {
        $store = Cache::store('database');

        $this->assertTrue($store->put('public:db-test:v1', ['value' => 42], 60));
        $this->assertSame(['value' => 42], $store->get('public:db-test:v1'));
        $this->assertDatabaseHas('cache', ['key' => config('cache.prefix').'public:db-test:v1']);
    }

    public function test_array_payloads_survive_the_database_store_round_trip(): void
    {
        $key = 'public:db-array:v1';
        $payload = [
            'site_name' => 'متجر الموجة',
            'count' => 7,
            'enabled' => true,
            'missing' => null,
            'nested' => ['levels' => [1, 2, 3], 'ratio' => 0.5],
        ];

        Cache::store('database')->put($key, $payload, 60);

        $this->assertSame($payload, Cache::store('database')->get($key));

        // The raw row must contain a serialised plain array, not an object.
        $raw = DB::table('cache')->where('key', config('cache.prefix').$key)->value('value');

        $this->assertIsString($raw);
        $this->assertSame($payload, unserialize($raw, ['allowed_classes' => false]));
    }

    public function test_public_cache_entries_are_stored_as_arrays_and_models_are_rehydrated(): void
    {
        $this->makeSettings(['site_name' => 'Matrix Wave']);
        $category = $this->makeCategory('Phones', 'phones');
        $this->makeProduct($category);
        $this->makeSocialLink('https://example.test/facebook', 1);

        $settings = SiteSetting::current();
        $categories = Category::publicWithProductCounts();
        $links = SocialLink::activeLinks();

        $this->assertInstanceOf(SiteSetting::class, $settings);
        $this->assertSame('Matrix Wave', $settings->site_name);

        $this->assertInstanceOf(Collection::class, $categories);

        $cachedCategory = $categories->firstWhere('id', $category->id);

        $this->assertInstanceOf(Category::class, $cachedCategory);
        $this->assertSame(1, (int) $cachedCategory->products_count);

        $this->assertInstanceOf(SocialLink::class, $links->first());
        $this->assertInstanceOf(SocialPlatform::class, $links->first()->platform);
        $this->assertSame('facebook', $links->first()->platform->value);

        $this->assertDatabaseHas('cache', ['key' => config('cache.prefix').PublicCache::SITE_SETTINGS]);
        $this->assertDatabaseHas('cache', ['key' => config('cache.prefix').PublicCache::ACTIVE_CATEGORIES]);
        $this->assertDatabaseHas('cache', ['key' => config('cache.prefix').PublicCache::SOCIAL_LINKS]);

        foreach ([PublicCache::SITE_SETTINGS, PublicCache::ACTIVE_CATEGORIES, PublicCache::SOCIAL_LINKS] as $key) {
            $payload = Cache::store('database')->get($key);

            $this->assertIsArray($payload);
            $this->assertStringNotContainsString('__PHP_Incomplete_Class', serialize($payload));
        }
    }

    public function test_a_warm_database_cache_lookup_uses_only_the_cache_table(): void
    {
        $this->makeSettings(['site_name' => 'Matrix Wave']);
        SiteSetting::current();

        app()->forgetInstance(SiteSetting::CURRENT_CACHE_KEY);

        $settings = null;

        $queries = $this->queriesDuring(function () use (&$settings): void {
            $settings = SiteSetting::current();
        });

        $this->assertSame('Matrix Wave', $settings?->site_name);
        $this->assertCount(1, $queries);
        $this->assertStringContainsString('cache', strtolower($queries[0]));
        $this->assertStringNotContainsString('site_settings', strtolower($queries[0]));
    }

    public function test_invalidation_removes_the_database_cache_entry(): void
    {
        $category = $this->makeCategory('Phones', 'phones');

        Category::publicWithProductCounts();

        $key = config('cache.prefix').PublicCache::ACTIVE_CATEGORIES;

        $this->assertDatabaseHas('cache', ['key' => $key]);

        $this->makeProduct($category);

        $this->assertDatabaseMissing('cache', ['key' => $key]);
    }

    public function test_expired_database_entries_return_null_and_are_removed(): void
    {
        $store = Cache::store('database');
        $key = 'public:db-expiry:v1';

        $store->put($key, ['value' => 42], 60);

        $this->assertSame(['value' => 42], $store->get($key));
        $this->assertDatabaseHas('cache', ['key' => config('cache.prefix').$key]);

        $this->travel(61)->seconds();

        $this->assertNull($store->get($key));
        $this->assertDatabaseMissing('cache', ['key' => config('cache.prefix').$key]);
    }

    public function test_cache_failures_fall_back_to_the_database_when_using_the_database_store(): void
    {
        Log::spy();

        config(['cache.stores.database.table' => 'missing_cache_table']);
        Cache::forgetDriver('database');

        $result = PublicCache::remember('public:db-fallback:v1', 60, fn (): array => ['value' => 42]);

        $this->assertSame(['value' => 42], $result);

        // A failed read short-circuits the write, so only one warning is logged.
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_deferred_invalidation_removes_the_database_entry_after_commit(): void
    {
        $category = $this->makeCategory('Phones', 'phones');

        $this->assertSame(0, $this->cachedProductCount($category));

        $key = config('cache.prefix').PublicCache::ACTIVE_CATEGORIES;

        $this->assertDatabaseHas('cache', ['key' => $key]);

        DB::transaction(function () use ($category, $key): void {
            $this->makeProduct($category);

            $this->assertDatabaseHas('cache', ['key' => $key]);
        });

        $this->assertDatabaseMissing('cache', ['key' => $key]);
        $this->assertSame(1, $this->cachedProductCount($category));
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

    private function makeSocialLink(string $url, int $sortOrder): SocialLink
    {
        return SocialLink::query()->create([
            'platform' => 'facebook',
            'url' => $url,
            'icon_key' => 'facebook',
            'is_active' => true,
            'sort_order' => $sortOrder,
        ]);
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

    /**
     * @return list<string>
     */
    private function queriesDuring(Closure $callback): array
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $callback();

        $queries = array_column(DB::getQueryLog(), 'query');

        DB::disableQueryLog();

        return $queries;
    }
}
