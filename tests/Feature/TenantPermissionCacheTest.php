<?php

namespace Tests\Feature;

use Illuminate\Cache\ArrayStore;
use Illuminate\Contracts\Cache\Repository;
use Spatie\Permission\PermissionRegistrar;
use Tests\TenantTestCase;

/**
 * TEN-01 — roles and permissions leaked between tenants through a shared cache.
 *
 * spatie/laravel-permission caches the entire role-to-permission map under ONE
 * key and resolves its store through $cacheManager->store(), a real method on
 * the base CacheManager — so it bypasses Stancl\Tenancy\CacheManager::__call(),
 * the only thing that would have applied a per-tenant tag. With the package
 * default of 'default' it landed on the `database` store, which config/cache.php
 * pins to the CENTRAL connection. One row, every tenant.
 *
 * It was reproducible: a role created only in tenant `acme` was live inside
 * tenant `green` and granted a `green` user a permission `green`'s own database
 * never gave them. Role ids are allocated independently per database, so which
 * tenant warmed the cache last decided what a given id meant for everyone.
 *
 * WHY THIS TEST ASSERTS CONFIGURATION RATHER THAN BEHAVIOUR
 *
 * phpunit.xml forces CACHE_STORE=array, so under test the default store is
 * already per-process and already safe. A behavioural "warm in tenant A, read in
 * tenant B" test would therefore pass here whether or not the bug is present —
 * it would assert nothing. That is the same trap described in TEN-02: the suite
 * runs on a store with different capabilities from production.
 *
 * So this pins the two things that actually differ between the fixed and broken
 * states, neither of which the ambient CACHE_STORE can mask.
 */
class TenantPermissionCacheTest extends TenantTestCase
{
    /**
     * Stores that outlive a single process, and so would be shared by every
     * tenant unless something tenant-scoped the key — which, for this package,
     * nothing does.
     *
     * @var array<int, string>
     */
    private const SHARED_DRIVERS = ['database', 'file', 'redis', 'memcached', 'dynamodb', 'apc'];

    public function test_the_permission_cache_is_not_pointed_at_a_shared_store(): void
    {
        $configured = config('permission.cache.store');

        $this->assertNotSame(
            'default',
            $configured,
            "permission.cache.store is 'default', which resolves to the central database store. "
            .'The role-to-permission map would be shared by every tenant.'
        );

        $driver = config("cache.stores.{$configured}.driver");

        $this->assertNotContains(
            $driver,
            self::SHARED_DRIVERS,
            "permission.cache.store points at the '{$driver}' driver, which persists beyond the "
            .'request and is not tenant-scoped. One tenant would serve another tenant its roles.'
        );
    }

    /**
     * The store the registrar actually holds, not just the configured name —
     * this is what a request would really read from.
     */
    public function test_the_registrar_resolves_a_per_process_store(): void
    {
        $cache = app(PermissionRegistrar::class)->getCacheRepository();

        $this->assertInstanceOf(Repository::class, $cache);
        $this->assertInstanceOf(
            ArrayStore::class,
            $cache->getStore(),
            'The permission registrar is caching to a store that survives the request. '
            .'Whichever tenant warms it last decides what every other tenant sees.'
        );
    }

    /**
     * The cache key is a single constant shared by every tenant. That is fine
     * while the store cannot be shared, and is the reason the store must not be
     * — recorded here so the coupling is visible if someone changes either.
     */
    public function test_the_cache_key_is_documented_as_global(): void
    {
        $this->assertSame('spatie.permission.cache', config('permission.cache.key'));
    }
}
