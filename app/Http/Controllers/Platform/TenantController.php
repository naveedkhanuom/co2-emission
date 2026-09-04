<?php

namespace App\Http\Controllers\Platform;

use App\Console\Commands\ProvisionTenant;
use App\Http\Controllers\Controller;
use App\Jobs\ProvisionTenantWorkspace;
use App\Models\Tenant;
use App\Support\TenantSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The client list, and the handful of things we do to a client account.
 *
 * Runs on the central connection throughout. Nothing here reads a client's
 * emissions data — deliberately: the back-office exists to administer
 * accounts, not to look inside them.
 */
class TenantController extends Controller
{
    public function index(Request $request)
    {
        $tenants = Tenant::query()
            ->with('domains')
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->input('search'), function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('id', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->orderByRaw('FIELD(status, ?, ?, ?, ?, ?, ?)',
                [
                    Tenant::STATUS_FAILED,
                    Tenant::STATUS_PROVISIONING,
                    Tenant::STATUS_PAST_DUE,
                    Tenant::STATUS_SUSPENDED,
                    Tenant::STATUS_ACTIVE,
                    Tenant::STATUS_ARCHIVED,
                ])
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('platform.tenants.index', [
            'tenants' => $tenants,
            'counts' => Tenant::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'filters' => $request->only(['status', 'search']),
            'statuses' => $this->statuses(),
            'centralDomain' => config('tenancy.central_domains')[0] ?? 'localhost',

            // Read from the stamp on each tenant row, so listing the whole fleet
            // stays one query against the central database. Answering this
            // properly would mean opening a connection per tenant.
            'expectedSchema' => TenantSchema::latestAvailable(),
        ]);
    }

    /**
     * Onboards a client by calling the same command the CLI uses, rather than
     * reimplementing it. Provisioning has rollback and reserved-name rules
     * that must not exist in two places and drift apart.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subdomain' => ['required', 'string', 'min:2', 'max:63'],
            'name' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255'],
        ]);

        $subdomain = Str::lower(trim($validated['subdomain']));

        // Generated here, not inside the command, so we know what it is. The
        // command only prints it to its own output buffer, which is discarded
        // on success — provisioning from this screen used to leave an account
        // whose password nobody had.
        $password = Str::password(16);

        // Cheap rejections first, while there is still a request to answer
        // into. Everything below is asynchronous, so a subdomain that was
        // never going to work should say so now rather than in a log file
        // three minutes later.
        if (($rejection = ProvisionTenant::rejectionFor($subdomain)) !== null) {
            return back()->withInput()->with('error', $rejection);
        }

        // Off the request. Provisioning creates a database, runs 88 migrations
        // and seeds several thousand factor rows — 10-11 seconds idle, and it
        // is the seeding that grows. Behind a 30s FPM timeout on a loaded
        // server this used to cut out part way through, leaving a half-built
        // account and an operator with no idea how far it got.
        ProvisionTenantWorkspace::dispatch($subdomain, [
            '--name' => $validated['name'] ?? null,
            '--company' => $validated['company'] ?? null,
            '--owner-name' => $validated['owner_name'] ?? null,
            '--owner-email' => $validated['owner_email'],
            '--owner-password' => $password,
        ]);

        $host = $subdomain.'.'.(config('tenancy.central_domains')[0] ?? 'localhost');

        return redirect()
            ->route('platform.tenants.index')
            ->with('credentials', [
                'subdomain' => $subdomain,
                'url' => 'http://'.$host.'/login',
                'email' => $validated['owner_email'],
                'password' => $password,
                // Local only: the web server already answers for *.domain,
                // but the Windows hosts file cannot wildcard, so the address
                // will not resolve until this line exists. In production a
                // single wildcard DNS record covers every client.
                'hosts_line' => app()->isLocal() ? '127.0.0.1 '.$host : null,

                // The credentials are known before provisioning starts — the
                // password is generated here — so they can be shown at once.
                // The WORKSPACE is not ready yet, and saying so is the
                // difference between "wait a moment" and "your login is
                // broken" when they try the link immediately.
                'pending' => true,
            ]);
    }

    public function suspend(Request $request, string $tenant): RedirectResponse
    {
        return $this->transition($tenant, Tenant::STATUS_SUSPENDED, 'suspended');
    }

    public function activate(Request $request, string $tenant): RedirectResponse
    {
        return $this->transition($tenant, Tenant::STATUS_ACTIVE, 'reactivated');
    }

    protected function transition(string $tenantId, string $status, string $verb): RedirectResponse
    {
        $tenant = Tenant::findOrFail($tenantId);

        // Archived and failed accounts are not part of the suspend/reactivate
        // cycle — reviving one is a decision that needs more than a button.
        if (in_array($tenant->status, [Tenant::STATUS_ARCHIVED, Tenant::STATUS_FAILED], true)) {
            return back()->with('error', "\"{$tenant->id}\" is {$tenant->status} and cannot be {$verb} from here.");
        }

        $previous = $tenant->status;
        $tenant->update(['status' => $status]);

        Log::info('Tenant status changed from the back-office', [
            'tenant' => $tenant->id,
            'from' => $previous,
            'to' => $status,
            'by' => auth('platform')->user()?->email,
        ]);

        return back()->with('status', "\"{$tenant->name}\" has been {$verb}.");
    }

    /**
     * @return array<string, string>
     */
    protected function statuses(): array
    {
        return [
            Tenant::STATUS_ACTIVE => 'Active',
            Tenant::STATUS_SUSPENDED => 'Suspended',
            Tenant::STATUS_PAST_DUE => 'Past due',
            Tenant::STATUS_PROVISIONING => 'Provisioning',
            Tenant::STATUS_FAILED => 'Failed',
            Tenant::STATUS_ARCHIVED => 'Archived',
        ];
    }
}
