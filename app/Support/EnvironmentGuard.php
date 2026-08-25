<?php

namespace App\Support;

use RuntimeException;

/**
 * Boot-time assertions about how the application is configured to run.
 *
 * These are deployment mistakes that Laravel itself will happily allow, and that
 * stay invisible until the moment they cost something. Checking them at boot
 * turns a silent, exploitable misconfiguration into an obvious startup failure.
 */
class EnvironmentGuard
{
    /**
     * Production must never serve with debug mode on.
     *
     * With APP_DEBUG=true, any unhandled exception renders the Ignition error
     * page: full filesystem paths, the failing SQL with its bindings, loaded
     * config and environment values — including database credentials and API
     * keys — to whoever triggered it. No authentication is required to see it;
     * an error on the login screen is enough.
     *
     * This fails closed on purpose. A misconfigured deploy is a one-line fix and
     * a few minutes of downtime; a leaked credential is neither.
     *
     * @throws RuntimeException when production is configured to debug.
     */
    public static function assertNotDebuggingInProduction(string $environment, bool $debug): void
    {
        if ($environment === 'production' && $debug) {
            throw new RuntimeException(
                'Refusing to boot: APP_DEBUG must be false when APP_ENV=production. '
                .'Debug mode publishes stack traces, configuration and database credentials '
                .'on any unhandled error. Set APP_DEBUG=false and run `php artisan config:clear`.'
            );
        }
    }
}
