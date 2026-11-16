<?php

namespace Tests\Feature\Caching;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Proves that the PHPUnit environment is pinned to the volatile array store and
 * the in-memory SQLite database, so test cleanup can never touch a real store.
 */
class CacheIsolationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_the_test_suite_runs_on_the_array_store_and_in_memory_sqlite(): void
    {
        $this->assertSame('array', config('cache.default'));
        $this->assertSame('array', Cache::getDefaultDriver());
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
    }

    public function test_flushing_the_test_store_does_not_touch_the_database_store(): void
    {
        $key = 'public:isolation-test:v1';
        $physicalKey = config('cache.prefix').$key;

        Cache::store('database')->put($key, ['value' => 42], 60);

        $this->assertDatabaseHas('cache', ['key' => $physicalKey]);

        Cache::store('array')->put($key, ['value' => 'array'], 60);
        Cache::store('array')->flush();

        $this->assertNull(Cache::store('array')->get($key));
        $this->assertSame(['value' => 42], Cache::store('database')->get($key));
        $this->assertDatabaseHas('cache', ['key' => $physicalKey]);
    }
}
