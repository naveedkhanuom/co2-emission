<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Tests\TestCase;

/**
 * tenant:hosts reports which clients do not resolve locally.
 *
 * Local development only. The web server already answers for *.your-domain,
 * so the sole missing piece is name resolution, and the Windows hosts file
 * has no wildcard. In production one wildcard DNS record covers every client
 * and this command is never needed.
 *
 * These tests only ever READ the hosts file — writing needs administrator
 * rights, and a test suite that edited system files would be worse than the
 * problem it verifies.
 */
class TenantHostsCommandTest extends TestCase
{
    public function test_it_lists_clients_whose_subdomain_has_no_entry(): void
    {
        $tenant = Tenant::find('phpunitstatus');

        if (! $tenant) {
            $this->markTestSkipped('No fixture tenant available.');
        }

        $tenant->update(['status' => Tenant::STATUS_ACTIVE]);

        $central = config('tenancy.central_domains')[0];

        $this->artisan('tenant:hosts')
            ->expectsOutputToContain('phpunitstatus.'.$central)
            ->assertSuccessful();
    }

    /**
     * Without --write it must only report. A command that edited the hosts
     * file merely for being run would be an unpleasant surprise.
     */
    public function test_it_does_not_touch_the_hosts_file_unless_asked(): void
    {
        $path = PHP_OS_FAMILY === 'Windows'
            ? getenv('SystemRoot').'\\System32\\drivers\\etc\\hosts'
            : '/etc/hosts';

        $before = md5_file($path);

        $this->artisan('tenant:hosts')->assertSuccessful();

        $this->assertSame($before, md5_file($path), 'A dry run must leave the hosts file alone.');
    }

    public function test_it_tells_you_how_to_apply_the_entries(): void
    {
        $this->artisan('tenant:hosts')
            ->expectsOutputToContain('--write')
            ->assertSuccessful();
    }

    /**
     * An archived client is gone as far as anyone visiting is concerned, so
     * there is no reason to make its address resolve.
     */
    public function test_archived_clients_are_left_out(): void
    {
        $tenant = Tenant::find('phpunitstatus');

        if (! $tenant) {
            $this->markTestSkipped('No fixture tenant available.');
        }

        $tenant->update(['status' => Tenant::STATUS_ARCHIVED]);

        try {
            $this->artisan('tenant:hosts')
                ->doesntExpectOutputToContain('phpunitstatus.')
                ->assertSuccessful();
        } finally {
            $tenant->update(['status' => Tenant::STATUS_ACTIVE]);
        }
    }
}
