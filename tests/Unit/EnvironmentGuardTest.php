<?php

namespace Tests\Unit;

use App\Support\EnvironmentGuard;
use RuntimeException;
use Tests\TestCase;

/**
 * The production/debug guard wired into AppServiceProvider::boot().
 *
 * The rule is narrow on purpose: it must stop production serving with debug on,
 * and must never get in the way of local development or the test suite — both of
 * which legitimately run with APP_DEBUG=true.
 */
class EnvironmentGuardTest extends TestCase
{
    public function test_production_with_debug_on_is_refused(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/APP_DEBUG must be false/');

        EnvironmentGuard::assertNotDebuggingInProduction('production', true);
    }

    public function test_production_with_debug_off_is_allowed(): void
    {
        EnvironmentGuard::assertNotDebuggingInProduction('production', false);

        $this->assertTrue(true, 'Production without debug must boot.');
    }

    public function test_local_development_may_debug(): void
    {
        EnvironmentGuard::assertNotDebuggingInProduction('local', true);

        $this->assertTrue(true, 'Local development must keep debug mode.');
    }

    public function test_the_test_suite_may_debug(): void
    {
        EnvironmentGuard::assertNotDebuggingInProduction('testing', true);

        $this->assertTrue(true, 'The test environment must keep debug mode.');
    }

    /**
     * Staging is not production and is not covered by this rule. Recorded so the
     * narrowness is a decision rather than an oversight.
     */
    public function test_staging_is_not_covered(): void
    {
        EnvironmentGuard::assertNotDebuggingInProduction('staging', true);

        $this->assertTrue(true, 'Only the production environment is guarded.');
    }

    /**
     * The guard is actually wired into the boot path — a guard nobody calls is
     * worth nothing, so this boots the real provider rather than trusting that
     * the call site exists.
     */
    public function test_the_provider_refuses_to_boot_a_debugging_production(): void
    {
        $originalEnv = $this->app['env'];
        $originalDebug = config('app.debug');

        try {
            $this->app['env'] = 'production';
            config(['app.debug' => true]);

            $this->expectException(RuntimeException::class);

            (new \App\Providers\AppServiceProvider($this->app))->boot();
        } finally {
            $this->app['env'] = $originalEnv;
            config(['app.debug' => $originalDebug]);
        }
    }
}
