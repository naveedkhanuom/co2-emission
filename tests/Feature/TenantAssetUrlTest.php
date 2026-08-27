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
    public function test_a_clients_uploaded_logo_is_served_from_their_own_storage(): void
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
                '/tenancy/assets/app/branding.png',
                $url,
                'A tenant-uploaded logo must be served from the tenant asset route.'
            );

            // The asset route reads storage_path("app/public/{$path}"), which
            // is the public disk's own root — so the stored path goes through
            // unchanged. An earlier version prefixed it with 'public/',
            // producing app/public/public/... and a 404 that looked like a
            // package fault. Fetching it is the assertion that catches that.
            $this->get($url)->assertOk();
        } finally {
            Setting::set('app_logo', null);
            Storage::disk('public')->delete('app/branding.png');
        }
    }

    /**
     * The same resolution the sidebar uses for a COMPANY's logo. It is the
     * one other place that generated /storage/ URLs, which inside a tenant
     * point at central storage where the file has never been.
     */
    public function test_a_stored_file_resolves_to_the_tenant_route(): void
    {
        $disk = Storage::disk('public');
        $disk->put('company_logos/acme.png', 'not-really-a-png');

        try {
            $url = stored_file_url('company_logos/acme.png');

            $this->assertStringContainsString('/tenancy/assets/company_logos/acme.png', $url);
            $this->assertStringNotContainsString('/storage/', $url);

            $this->get($url)->assertOk();
        } finally {
            $disk->delete('company_logos/acme.png');
        }
    }

    public function test_a_full_url_is_left_alone(): void
    {
        $cdn = 'https://cdn.example.com/logo.avif';

        $this->assertSame($cdn, stored_file_url($cdn));
    }

    public function test_nothing_stored_resolves_to_nothing(): void
    {
        $this->assertNull(stored_file_url(null));
        $this->assertNull(stored_file_url(''));
    }

    public function test_the_default_is_returned_when_no_logo_is_set(): void
    {
        Setting::set('app_logo', null);

        $this->assertSame('fallback.png', app_logo_url('fallback.png'));
    }
}
