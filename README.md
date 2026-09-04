# GreenCRM — GHG emissions platform

A multi-tenant greenhouse-gas accounting application. Clients record Scope 1, 2
and 3 activity data; the platform prices it against published emission factors,
keeps the provenance an assurer needs, and produces disclosure reports — plus an
optional regulated **MRV** layer that fills the Environment Agency – Abu Dhabi
monitoring-plan workbook.

Laravel 11 · PHP 8.2 · MySQL · `stancl/tenancy` v3, database-per-tenant.

---

## The one thing to understand first

**The application is not served on one domain.** There are two kinds of host:

| | Where | What lives there |
|---|---|---|
| **Central** | `TENANCY_CENTRAL_DOMAINS` | The marketing page and `/admin`, the back-office where staff create client accounts. No client data is reachable. |
| **Tenant** | `{client}.{central-domain}` | The whole application, on that client's own subdomain, against that client's own database. |

`routes/tenant.php` loads `routes/web.php` behind subdomain identification, so
**there is no path by which an application route is served without a tenant.**
Visiting `/home` on the central domain is a 404, not a login page. If you have
just cloned this and the app looks like it has no routes, that is why.

Inside each client's database there is a **second** boundary: `company_id`, so
one client can hold several companies (a holding group and its subsidiaries)
without a database each.

---

## Local setup

### 1. Prerequisites

- PHP 8.2+ with the usual Laravel extensions
- **MySQL** — see the note in `.env.example`; SQLite cannot work here
- Node 18+
- Composer

### 2. Install

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

### 3. Point the central domains somewhere that wildcards

Clients live on subdomains, so local development needs `*.something` to resolve.
`.env.example` ships with the easiest answer:

```
TENANCY_CENTRAL_DOMAINS=127.0.0.1.nip.io,127.0.0.1,localhost
```

`nip.io` resolves `anything.127.0.0.1.nip.io` to `127.0.0.1` with no hosts-file
editing, so `acme.127.0.0.1.nip.io` just works.

If you would rather use a real local domain (`ghg.test` via Herd/Valet), set it
as the **first** entry — sign-in URLs and `/admin` are built from the first
central domain — and add a hosts line per client. `php artisan tenant:hosts
--write` does that for every client at once, from an administrator terminal.
Windows cannot wildcard the hosts file; in production one wildcard DNS record
covers everything.

### 4. Create the central database and migrate it

```bash
mysql -uroot -e "CREATE DATABASE ghg_emission_saas"
php artisan migrate
```

The central database holds only `tenants`, `domains`, `platform_users`,
sessions, cache and jobs — nine migrations. Client tables live in
`database/migrations/tenant/` and run per client.

### 5. Start a queue worker — provisioning depends on it

```bash
php artisan queue:work
```

**Without a worker, creating a client account silently does nothing.** The
back-office reports success and shows the credentials, and the workspace never
appears. Provisioning runs off the request because it takes 10–15 seconds, and
`ProvisionTenantWorkspace` is a queued job.

### 6. Create a back-office account, then a client

```bash
php artisan platform:user
php artisan tenant:provision acme --owner-email=you@example.test
```

Sign in to the back-office at `http://127.0.0.1.nip.io:8000/admin`, and to the
client workspace at `http://acme.127.0.0.1.nip.io:8000`.

`tenant:provision` creates the database, runs the tenant migrations, seeds roles
and permissions and the reference factor catalogues, creates the first company,
and makes an owner. It prints the password once and stores it nowhere.

### 7. Run it

```bash
php artisan serve
npm run dev
```

---

## Tests

```bash
php artisan test
```

**Tests run against real MySQL, not an in-memory database** — the sqlite lines
in `phpunit.xml` are commented out deliberately, because database-per-tenant and
the MySQL-only SQL cannot be exercised on SQLite.

Tests that touch the application extend `Tests\TenantTestCase`, which provisions
a persistent `phpunit` tenant on first run (a few seconds, once) and reuses it
afterwards inside a transaction. Tests of the central domain and the back-office
extend `Tests\TestCase`.

To rebuild the test tenant:

```bash
php artisan tinker --execute='App\Models\Tenant::find("phpunit")?->delete();'
```

---

## Things that will bite you

**A tenant migration does not reach existing clients on its own.** `tenants:migrate`
is a separate step, and `EnsureTenantSchemaIsCurrent` answers a 503 rather than
serving a client whose database is behind the code. After migrating, re-stamp:

```bash
php artisan tenants:migrate
php artisan tenants:each schema:stamp
```

**The built-in factor catalogue exists twice.** It is authored in
`config/scope1_sources.php` / `scope2_sources.php` and compiled into each
client's `emission_factors`. Edit the config and the compiled rows go stale —
the entry pages show the new number while reports and MRV show the old one.

```bash
php artisan tenants:each "factors:import builtin"
php artisan tenants:each factors:check-drift    # non-zero exit if any client is stale
```

**Commands that touch client data refuse to run without a tenant.** Run them
through `tenants:each`, which is the documented way:

```bash
php artisan tenants:each "factors:import defra"
```

**The EAD MRV workbook template is not in this repository** and must not be
committed — it carries a confidentiality notice and is issued per operator. The
`/mrv` export needs it installed at
`storage/app/templates/ead_deliverable_c.xlsx`, or `MRV_EAD_TEMPLATE_PATH`
pointing at it. Without it the export fails with a message saying so.

**Broadcasting is off.** `.env` sets `BROADCAST_DRIVER`, which Laravel 11 does
not read (it wants `BROADCAST_CONNECTION`), so Reverb and Echo are installed
against a null driver. Do not turn it on without reading `TEN-04` in the audit
first: channel names are not tenant-scoped, so `private-user.1` is currently the
same channel for every client.

---

## Where things are

| Path | |
|---|---|
| `routes/central.php` | Marketing page, `/admin` back-office |
| `routes/tenant.php` | The middleware stack, then loads `web.php` |
| `routes/web.php` | The application, ~250 routes |
| `app/Services/Factors/` | Factor resolution, catalogue compiling, publisher imports |
| `app/Services/Boundary/` | The Boundary Advisor — what a client needs to measure |
| `app/Services/MRV/` | EAD / EU-ETS regulated layer and the workbook filler |
| `app/Services/AI/` | Claude and OpenAI — extraction, classification, matching |
| `app/Support/` | `Gwp`, `TenantSchema`, `Auditing`, `DeveloperAccount` |
| `database/migrations/` | Central — 9 files |
| `database/migrations/tenant/` | Per client — 90 files |
| `database/factors/sources/` | Publishers' own files, hashed, with `MANIFEST.md` |

## Documentation

| | |
|---|---|
| [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) | Production setup, the deploy script, operational runbook |
| [`docs/SAAS_TENANCY_AUDIT.md`](docs/SAAS_TENANCY_AUDIT.md) | Findings from the multi-tenant conversion, with what is fixed and what is open |
| [`docs/CODEBASE_AUDIT.md`](docs/CODEBASE_AUDIT.md) | The earlier audit; most items closed, `GHG-04` partly |
| [`docs/FACTOR_RECONCILIATION.md`](docs/FACTOR_RECONCILIATION.md) | Generated — how the two factor catalogues compare |
| [`MRV_EAD_PLAN.md`](MRV_EAD_PLAN.md) | Why the MRV layer is additive, and what it needs |
| [`AI_FEATURES.md`](AI_FEATURES.md) | AI features, shipped and planned |

Both audits carry stable finding ids (`TEN-01`, `GHG-04`) that the code comments
refer to. When a comment says "see TEN-06", that is where.

---

## Conventions worth matching

**Comments say why, not what.** The codebase is unusually heavily commented and
the comments explain decisions — why the pipeline must stay synchronous, why a
factor is derived rather than transcribed, why a default is `false`. Match that
rather than describing what the next line does.

**A regression test must fail against the code it guards.** A test that passes
either way documents a behaviour instead of protecting one. Several test
docblocks record having been checked against the pre-fix code.

**Never invent a regulatory number.** `database/factors/sources/MANIFEST.md` is
the policy: publishers' own files, committed unmodified and hashed — never a
reseller's copy, never transcribed values. Where a figure has to be produced, it
is *derived* from committed inputs so it can be reconciled.

**Style:** `php vendor/bin/pint` before committing.
