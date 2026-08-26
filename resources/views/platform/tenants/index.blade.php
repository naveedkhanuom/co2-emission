@extends('platform.layout')

@section('title', 'Clients')

@section('content')
    <h1>Clients</h1>
    <p class="sub">
        {{ $tenants->total() }} {{ Str::plural('account', $tenants->total()) }}.
        Each has its own database and its own address.
    </p>

    @if ($credentials = session('credentials'))
        <div class="card" style="border-color: var(--ok);">
            <h2 style="color: var(--ok);">“{{ $credentials['subdomain'] }}” is ready</h2>
            <p style="margin-top: 0;">
                <strong>Copy these now.</strong> The password is not stored anywhere and cannot be
                shown again. Send it over a channel you trust and have them change it.
            </p>
            <table style="max-width: 34rem;">
                <tr><th style="width: 8rem;">Sign in at</th><td class="mono">{{ $credentials['url'] }}</td></tr>
                <tr><th>Email</th><td class="mono">{{ $credentials['email'] }}</td></tr>
                <tr><th>Password</th><td class="mono"><strong>{{ $credentials['password'] }}</strong></td></tr>
            </table>
        </div>
    @endif

    <div class="card">
        <h2>Onboard a client</h2>
        <form method="POST" action="{{ route('platform.tenants.store') }}">
            @csrf
            <div class="grid">
                <div>
                    <label for="subdomain">Subdomain</label>
                    <input id="subdomain" type="text" name="subdomain" value="{{ old('subdomain') }}"
                           placeholder="acme" required>
                    <small class="muted mono">….{{ $centralDomain }}</small>
                </div>
                <div>
                    <label for="name">Account name</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="Acme Holding Group">
                </div>
                <div>
                    <label for="company">First company</label>
                    <input id="company" type="text" name="company" value="{{ old('company') }}" placeholder="Acme Industries Ltd">
                </div>
                <div>
                    <label for="owner_name">Owner name</label>
                    <input id="owner_name" type="text" name="owner_name" value="{{ old('owner_name') }}">
                </div>
                <div>
                    <label for="owner_email">Owner email</label>
                    <input id="owner_email" type="email" name="owner_email" value="{{ old('owner_email') }}" required>
                </div>
                <div style="display: flex; align-items: flex-end;">
                    <button type="submit" class="primary">Provision</button>
                </div>
            </div>
            <p class="muted" style="margin: .9rem 0 0; font-size: .85rem;">
                Creates the database, its subdomain, one company and the owner. Adding further
                companies is done inside the workspace — it does not create another database.
            </p>
        </form>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('platform.tenants.index') }}"
              style="display: flex; gap: .6rem; align-items: flex-end; flex-wrap: wrap;">
            <div style="flex: 1 1 14rem;">
                <label for="search">Search</label>
                <input id="search" type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name or subdomain">
            </div>
            <div style="flex: 0 1 12rem;">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>
                            {{ $label }}{{ isset($counts[$value]) ? ' ('.$counts[$value].')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div><button type="submit">Filter</button></div>
        </form>
    </div>

    <div class="card">
        <div class="tablewrap">
            <table>
                <thead>
                    <tr>
                        <th>Account</th>
                        <th>Address</th>
                        <th>Database</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tenants as $tenant)
                        <tr>
                            <td>
                                <strong>{{ $tenant->name }}</strong><br>
                                <span class="muted mono">{{ $tenant->id }}</span>
                            </td>
                            <td class="mono">
                                @php($subdomain = $tenant->domains->first()?->domain)
                                @if ($subdomain)
                                    {{ $subdomain }}.{{ $centralDomain }}
                                @else
                                    <span class="muted">— no address —</span>
                                @endif
                            </td>
                            <td class="mono muted">tenant_{{ $tenant->id }}</td>
                            <td>
                                <span class="pill pill-{{ $tenant->status }}">{{ $statuses[$tenant->status] ?? $tenant->status }}</span>
                            </td>
                            <td class="muted">{{ $tenant->created_at?->format('j M Y') }}</td>
                            <td class="right">
                                @if ($tenant->status === \App\Models\Tenant::STATUS_ACTIVE)
                                    <form class="inline" method="POST" action="{{ route('platform.tenants.suspend', $tenant->id) }}"
                                          onsubmit="return confirm('Suspend {{ $tenant->name }}? Everyone there is signed out of the workspace until it is reactivated. No data is deleted.');">
                                        @csrf
                                        <button type="submit">Suspend</button>
                                    </form>
                                @elseif (in_array($tenant->status, [\App\Models\Tenant::STATUS_SUSPENDED, \App\Models\Tenant::STATUS_PAST_DUE], true))
                                    <form class="inline" method="POST" action="{{ route('platform.tenants.activate', $tenant->id) }}">
                                        @csrf
                                        <button type="submit">Reactivate</button>
                                    </form>
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="muted" style="padding: 1.5rem 0; text-align: center;">
                                No accounts match.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($tenants->hasPages())
            <div style="margin-top: 1rem;">{{ $tenants->links() }}</div>
        @endif
    </div>
@endsection
