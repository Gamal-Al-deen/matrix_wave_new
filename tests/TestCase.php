<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Values that keep every test away from real caches and databases.
     *
     * @var array<string, string>
     */
    private const ISOLATED_ENVIRONMENT = [
        'CACHE_STORE' => 'array',
        'DB_CONNECTION' => 'sqlite',
        'DB_DATABASE' => ':memory:',
    ];

    protected function setUp(): void
    {
        $this->forceIsolatedEnvironment();

        parent::setUp();

        $this->assertIsolatedEnvironment();
    }

    /**
     * Laravel reads $_SERVER before $_ENV, and PHPUnit's force flag only writes
     * _ENV and putenv(), so pin all three before the application boots.
     */
    private function forceIsolatedEnvironment(): void
    {
        foreach (self::ISOLATED_ENVIRONMENT as $name => $value) {
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }

    /**
     * Fail loudly instead of letting a misconfigured suite migrate or flush a
     * real database or cache store.
     */
    private function assertIsolatedEnvironment(): void
    {
        $cacheStore = config('cache.default');
        $connection = config('database.default');
        $database = $connection === 'sqlite'
            ? DB::connection($connection)->getDatabaseName()
            : null;

        if ($cacheStore === 'array' && $connection === 'sqlite' && $database === ':memory:') {
            return;
        }

        throw new RuntimeException(sprintf(
            'Refusing to run: the test environment is not isolated '
            .'(cache=%s, connection=%s, database=%s). '
            .'Expected array, sqlite and :memory:.',
            var_export($cacheStore, true),
            var_export($connection, true),
            var_export($database, true),
        ));
    }
}
