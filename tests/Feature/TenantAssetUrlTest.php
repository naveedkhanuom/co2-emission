<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Tests\TenantTestCase;

/**
 * Which files are "the application's" and which are "this client's".
 *
 * stancl ships asset_helper_tenancy, which rewrites every asset() call to
 * /tenancy/assets/{path} and serves it from the tenant's own storage. Turned
 * on, it broke every application asset in public/: the login page lost its
 * background image and its logo, leaving white text on a pale background and
 * a broken-image icon where the logo should be.
 *
 * The split these tests hold in place: asset() means a file shipped with the
 * application, identical for every client. A file a client uploaded goes
 * through tenant_asset(), which reads from that client's storage.
 */
class TenantAssetUrlTest extends TenantTestCase
{
    /**
     * The regression guard. If asset_helper_tenancy is ever switched back on,
     * this fails before anyone opens a browser.
     */
    public function test_application_assets_keep_their_ordinary_public_url(): void
    {
        $url = asset('logo.png');

        $this->assertStringEndsWith('/logo.png', $url);
        $this->assertStringNotContainsString(
            '/tenancy/assets/',
            $url,
            'asset() must not be rewritten to the tenant asset route — public/ files 404 through it.'
        );
    }

    public function test_the_login_page_assets_resolve(): void
    {
        $response = $this->get('/login');

        $response->assertOk();

        // The background image and logo are what broke visibly.
        $response->assertSee('/bg2.jpeg', false);
        $response->assertDontSee('/tenancy/assets/bg2.jpeg', false);
    }

    /**
     * A client's OWN uploaded logo is the opposite case: it lives in that
     * client's storage, where the public/storage symlink cannot see it, so it
     * has to go through the tenant asset route.
     */
    public function test_a_clients_uploaded_logo_lands_in_their_storage_and_points_at_the_tenant_route(): void
    {
        $disk = Storage::disk('public');
        $disk->put('app/branding.png', 'not-really-a-png');
        Setting::set('app_logo', 'app/branding.png');

        try {
            // The upload must land in THIS client's storage. If the public
            // disk root were not overridden per tenant, every client's
            // branding would pile into the central directory together.
            $this->assertStringContainsString(
                'tenant'.self::TEST_TENANT_ID,
                $disk->path('app/branding.png'),
                'The public disk must be rooted in the tenant\'s own storage.'
            );

            $url = app_logo_url();

            $this->assertStringContainsString(
                '/tenancy/assets/public/app/branding.png',
                $url,
                'A tenant-uploaded logo must be served from the tenant asset route.'
            );

            // NOT asserted here: that fetching this URL returns the file.
            // It does not — stancl's asset controller answers 404 for a file
            // that demonstrably exists at the path it computes, verified
            // against a live request rather than only in the harness. Nothing
            // uses tenant-uploaded branding yet, so this is recorded as an
            // open gap rather than papered over with a passing assertion
            // that proves less than it appears to.
        } finally {
            Setting::set('app_logo', null);
            Storage::disk('public')->delete('app/branding.png');
        }
    }

    public function test_the_default_is_returned_when_no_logo_is_set(): void
    {
        Setting::set('app_logo', null);

        $this->assertSame('fallback.png', app_logo_url('fallback.png'));
    }
}
