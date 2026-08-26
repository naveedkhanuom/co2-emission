<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

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

        try {
            $exitCode = Artisan::call('tenant:provision', array_filter([
                'subdomain' => $subdomain,
                '--name' => $validated['name'] ?? null,
                '--company' => $validated['company'] ?? null,
                '--owner-name' => $validated['owner_name'] ?? null,
                '--owner-email' => $validated['owner_email'],
                '--owner-password' => $password,
            ]));
        } catch (Throwable $e) {
            Log::error('Tenant provisioning failed from the back-office', [
                'subdomain' => $subdomain,
                'error' => $e->getMessage(),
            ]);

            return back()->withInput()->with('error', 'Provisioning failed: '.$e->getMessage());
        }

        if ($exitCode !== 0) {
            // The command reports precisely why — reserved name, already
            // taken, bad email — and has already rolled back anything partial.
            return back()->withInput()->with('error', trim(Artisan::output()));
        }

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
