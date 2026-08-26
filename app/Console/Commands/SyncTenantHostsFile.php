<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Throwable;

/**
 * Adds a hosts entry for every client, so their subdomains resolve locally.
 *
 * Purely a local-development convenience. The web server already answers for
 * *.your-domain — Herd writes that wildcard into server_name itself — so the
 * only thing missing is name resolution, and the Windows hosts file has no
 * wildcard support. In production a single wildcard DNS record covers every
 * client, existing and future, and none of this is needed.
 *
 * Writing to the hosts file needs administrator rights, so this reports what
 * is missing when it cannot write, rather than failing obscurely.
 */
class SyncTenantHostsFile extends Command
{
    protected $signature = 'tenant:hosts
        {--write : Actually add the missing entries. Needs an elevated terminal}';

    protected $description = 'Add a local hosts entry for each client so their subdomain resolves';

    protected const MARKER = '# GHG SaaS tenants — managed by `php artisan tenant:hosts`';

    public function handle(): int
    {
        $path = $this->hostsPath();

        if (! is_readable($path)) {
            $this->components->error("Cannot read the hosts file at {$path}.");

            return self::FAILURE;
        }

        $contents = file_get_contents($path);
        $missing = $this->missingEntries($contents);

        if ($missing === []) {
            $this->components->info('Every client already resolves. Nothing to add.');

            return self::SUCCESS;
        }

        $this->components->warn(count($missing).' client(s) do not resolve yet:');
        $this->newLine();

        foreach ($missing as $line) {
            $this->line('    '.$line);
        }

        $this->newLine();

        if (! $this->option('write')) {
            $this->components->info(
                'Run `php artisan tenant:hosts --write` from an ADMINISTRATOR terminal to add these, '
                .'or paste them into '.$path.' yourself.'
            );

            return self::SUCCESS;
        }

        return $this->write($path, $contents, $missing);
    }

    /**
     * @return array<int, string>
     */
    protected function missingEntries(string $contents): array
    {
        $central = config('tenancy.central_domains')[0] ?? null;

        if (! $central) {
            return [];
        }

        return Tenant::query()
            ->with('domains')
            ->whereNot('status', Tenant::STATUS_ARCHIVED)
            ->get()
            ->map(fn (Tenant $tenant) => $tenant->domains->first()?->domain)
            ->filter()
            ->map(fn (string $subdomain) => $subdomain.'.'.$central)
            ->reject(function (string $host) use ($contents) {
                // Match the host as a whole word, so acme.example.test is not
                // considered present because beta-acme.example.test is.
                return (bool) preg_match('/^[^#\r\n]*\s'.preg_quote($host, '/').'\s*$/m', $contents);
            })
            ->map(fn (string $host) => '127.0.0.1 '.$host)
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $missing
     */
    protected function write(string $path, string $contents, array $missing): int
    {
        $block = str_contains($contents, self::MARKER) ? '' : "\n".self::MARKER;
        $block .= "\n".implode("\n", $missing)."\n";

        try {
            if (@file_put_contents($path, rtrim($contents, "\r\n")."\n".$block, FILE_APPEND) === false) {
                throw new \RuntimeException('write returned false');
            }
        } catch (Throwable $e) {
            $this->newLine();
            $this->components->error(
                'Could not write to the hosts file — this needs an ADMINISTRATOR terminal. '
                .'Right-click your terminal and "Run as administrator", then run this again. '
                .'Or paste the lines above into '.$path.' by hand.'
            );

            return self::FAILURE;
        }

        $this->components->info('Added '.count($missing).' entr'.(count($missing) === 1 ? 'y' : 'ies').'. Those clients resolve now.');

        return self::SUCCESS;
    }

    protected function hostsPath(): string
    {
        return PHP_OS_FAMILY === 'Windows'
            ? getenv('SystemRoot').'\\System32\\drivers\\etc\\hosts'
            : '/etc/hosts';
    }
}
