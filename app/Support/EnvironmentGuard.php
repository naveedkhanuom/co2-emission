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

    /**
     * The session cookie must not be shared across tenant subdomains.
     *
     * Tenants are separated by subdomain, and a cookie scoped to the base
     * domain is sent to every one of them — that is what the Domain attribute
     * means, with or without a leading dot. One session would then travel from
     * acme.example.com to beta.example.com carrying acme's session payload,
     * including the selected company id, which the next request resolves
     * against BETA's database. The user lands on a real record belonging to a
     * different client.
     *
     * Leaving SESSION_DOMAIN unset scopes the cookie to the exact host, which
     * is what we want. The dangerous value is the one someone sets later to
     * "make login work across subdomains" — which is precisely the behaviour
     * that must not work.
     *
     * @param  array<int, string>  $centralDomains
     *
     * @throws RuntimeException when the cookie would span tenants.
     */
    public static function assertSessionCookieIsNotSharedAcrossTenants(?string $sessionDomain, array $centralDomains): void
    {
        if ($sessionDomain === null || trim($sessionDomain) === '') {
            return;
        }

        $candidate = ltrim(trim($sessionDomain), '.');

        foreach ($centralDomains as $central) {
            // Shared if the cookie is scoped to a central domain itself, or to
            // any parent of one — both reach every tenant subdomain under it.
            if ($candidate === $central || str_ends_with($central, '.'.$candidate)) {
                throw new RuntimeException(
                    "Refusing to boot: SESSION_DOMAIN is set to \"{$sessionDomain}\", which shares one session "
                    ."cookie across every tenant subdomain of \"{$central}\". A session opened on one client's "
                    .'subdomain would be presented on another, and the company id it carries would then be '
                    .'resolved against the wrong database. Unset SESSION_DOMAIN so the cookie is scoped to the '
                    .'exact host, and run `php artisan config:clear`.'
                );
            }
        }
    }
}
