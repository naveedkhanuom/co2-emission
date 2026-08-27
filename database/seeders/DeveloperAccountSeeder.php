<?php

namespace Database\Seeders;

use App\Support\DeveloperAccount;
use Illuminate\Database\Seeder;

/**
 * Adds the standing developer account to a newly provisioned client.
 *
 * Runs last in TenantDatabaseSeeder, after roles exist for it to be given.
 * Does nothing when the account is disabled or unconfigured — see
 * config/tenant_defaults.php, and read the warning there before enabling it
 * anywhere real.
 */
class DeveloperAccountSeeder extends Seeder
{
    public function run(): void
    {
        $user = DeveloperAccount::ensure();

        if ($user === null) {
            $this->command?->getOutput()->writeln(
                '  <fg=gray>Developer account skipped (disabled or unconfigured).</>'
            );
        }
    }
}
