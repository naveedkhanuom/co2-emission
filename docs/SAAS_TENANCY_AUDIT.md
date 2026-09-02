# SaaS Tenancy Audit — Real Gaps & Issues

> **What this is:** a full read of the codebase on the `saas-tenancy` branch, focused on
> what the multi-tenant conversion actually changed and what it left behind. Every
> finding below was reproduced against the running application or the live databases —
> none is inferred from naming or assumed from a pattern.
>
> **How it relates to `CODEBASE_AUDIT.md`:** that audit covered the `boundary` branch
> (2026-08-25) and most of its P0–P2 items are now closed. This one covers the tenancy
> layer, which did not exist when it was written, and records which of its findings
> remain open.

- **Date:** 2026-08-27
- **Branch:** `saas-tenancy` (clean, at `5c6c109`)
- **Scope:** 53 controllers · 37 models · 9 central + 85 tenant migrations · 247 routes · 329 tests passing
- **Architecture:** `stancl/tenancy` v3, database-per-tenant, subdomain identification,
  with a second `company_id` boundary *inside* each tenant database
- **Web version:** https://claude.ai/code/artifact/ad8994b8-593a-463d-8381-ce351f37958f

Findings carry stable IDs (`TEN-01`…`TEN-12`).

---

## Status — 2026-08-31

Re-verified against the code, not against this document. Suite: **345 passing**
(329 + 16 new).

Each new test was confirmed to FAIL against the pre-fix code before being kept —
a regression test that passes either way documents a behaviour rather than
guarding one. `CompanySelectionPersistenceTest` fails 3 of its 5 cases on the
old middleware; `SupportingDocumentPrivacyTest` fails its disk assertion on the
old controller.

| Finding | State | Where |
|---|---|---|
| `TEN-01` roles/permissions leak via shared cache | **Fixed** | `config/permission.php` → `'store' => 'array'`; `TenantPermissionCacheTest` |
| `TEN-13` bulk manual entry bypasses the period lock | **Fixed** | `EmissionRecordController::store()`; `BulkEntryPeriodLockTest` |
| `TEN-14` client uploads served unauthenticated | **Fixed** | uploads moved to the private disk; `SupportingDocumentPrivacyTest` |
| `TEN-15` draft writers had no provenance | **Fixed** | OCR + supplier survey now enrich; `DraftWriterProvenanceTest` |
| `TEN-16` draft writers ignored the period lock | **Fixed** | all three writers check; `DraftWriterProvenanceTest` |
| `TEN-02` `Cache::` throws inside a tenant request | Open | needs Redis, or drop `CacheTenancyBootstrapper` |
| `TEN-03` three Laravel 10 `.env` keys | Open in `.env` | documented in `docs/DEPLOYMENT.md` §5 |
| `TEN-04` realtime layer not tenant-scoped | Open | dormant while `TEN-03` keeps broadcasting off |
| `TEN-05` shared developer account | Open | `TENANT_DEV_ACCOUNT_ENABLED=true` in `.env` |
| `TEN-06` nothing migrates existing tenants | **Fixed** | `schema_version` now written and read; `EnsureTenantSchemaIsCurrent` + `schema:stamp` + back-office badge; `TenantSchemaDriftTest` |
| `GHG-04` factor catalogue — DEFRA imported | **Phase 1 part done** | `factors:import defra`; 2,622 factors with full provenance in all 5 tenants; `DefraFactorImportTest`. EPA 2025 still to import. |
| `TEN-07` provisioning runs inline | Open | timeout guidance added to deployment doc |
| `TEN-08` no offboarding, export or billing | Open | product decision |
| `TEN-09` owner's company selection cleared by saves | **Fixed** | `SetCompanyConnection` reads `input()` and guards emptiness; `CompanySelectionPersistenceTest` |
| `TEN-10` central DB holds the old application schema | Code clean | central migration set is now 9 files; live DB still needs the one-off cleanup |
| `TEN-11` new tenants born with 485 audit rows | **Fixed** | `App\Support\Auditing::without()` wraps seeding; `SeedingAuditNoiseTest` |
| `TEN-17` legacy documents on the public disk | **Tooling ready** | `documents:privatise`; `PrivatiseStoredDocumentsTest` — **must still be run against live tenants** |
| `TEN-18` dead `EnsureCompanyAccess` middleware | **Fixed** | deleted; `SetCompanyConnection` is the live one |
| `TEN-19` `AskController` had no permission gate | **Fixed** | gated on `list-dashboard`; `AskAssistantAuthorisationTest` |
| `TEN-12` CI does not run on this branch | **Fixed** | `tests.yml` now triggers on every push, not a branch list |

Two findings below were **not** in the original twelve and are recorded here with
new ids:

### TEN-13 · Bulk manual entry wrote into locked reporting periods

`EmissionRecordController::store()` has two branches. The bulk branch
(`if ($request->has('entries'))`) validates, checks ownership, then creates — and
returned before ever reaching the `assertPeriodOpen()` call that guards the
single-entry path below it.

Worse than the import hole this platform already closed (`GHG-01`): bulk rows are
written with the status from the request, which defaults to `active`, so they
landed straight in the reported inventory without passing through the review
queue that would otherwise have caught them. A locked year is a signed-off
inventory.

**Fixed** by guarding inside the first pass, which runs before any row is
created — so the batch stays atomic and a locked row anywhere in it rejects the
whole submission.

### TEN-15 · The draft-creating writers stored figures with no provenance

Three paths create `EmissionRecord`s outside the manual-entry controller: the
utility-bill OCR upload, the supplier-survey converter, and AI document
extraction. Only the third ran `EmissionEnrichmentService`. The other two wrote
rows with no `gwp_version`, no `activity_unit` and no `emission_factor_id` — the
same defect as the closed `GHG-02`, in doors that were missed.

A figure stating no GWP basis cannot be disclosed under CSRD/ESRS E1 or CDP.
Supplier-reported figures are also the ones an assurer scrutinises hardest, so
they least of all should arrive unstamped.

Two of the three already *knew* the unit and discarded it. The converter wrote it
into the notes prose only. The AI extraction screen renders an editable unit
column that `saveBtn` never put in its payload — the same shape as `GHG-26`,
where Manual Entry's unit dropdown posted a field name the controller did not
read.

**Fixed** by running `enrich()` on all three and persisting `activity_unit`
(blade included).

### TEN-16 · None of them consulted the reporting-period lock

The approval step already refuses to activate a record in a locked year, so this
could not reach a finalised inventory — but it let drafts accumulate against a
closed year that nobody could ever action, indistinguishable in the review queue
from real work.

**Fixed** per path, each in the way that suits it:

- **AI extraction** rejects the whole save with a 422 naming the row and year,
  in a pass that runs before any record is created.
- **OCR upload** still stores the bill — the file is evidence regardless of which
  year it lands in — and adds a warning saying no record was created.
- **Supplier survey** returns quietly and, critically, does **not** stamp
  `emissions_generated_at`. Conversion is idempotent on that column, so marking
  it would strand the supplier's answers permanently; leaving it null means the
  survey converts normally once the period is reopened. This path is reachable
  from the public portal, where the submitter is an unauthenticated third party
  who should not be shown the buyer's reporting calendar.

### TEN-14 · Client uploads were served with no authentication

The public disk is not reachable through `public/storage` under tenancy — it
lives in `storage/tenant{id}/app/public` — so it is served instead by stancl's
`/tenancy/assets/{path}`. Confirmed via `route:list`: that route's only
middleware is `InitializeTenancyBySubdomain`. No `web`, no `auth`, no company
check, and no `EnsureTenantIsActive`, so even a **suspended** client's files
stayed downloadable.

Supporting documents, AI-extraction source files and energy-certificate
documents were all being written there. These are the evidence an ISO 14064-3
assurer reads, and they carry account numbers and site addresses.
`downloadDocument()` had always checked company ownership, but that check was
decorative while the same bytes sat behind an open route.

**Fixed** by writing all three to the private `local` disk. Logos stay public
because they render on the unauthenticated login screen. The download action
falls back to the public disk so documents uploaded before the change keep
working — that fallback should be removed once the legacy files have been moved.

---

## Summary

| Band | Theme | Count |
|---|---|---|
| **P0** | Tenant isolation is not actually holding | 2 |
| **P1** | Configuration that silently does nothing | 3 |
| **P2** | Operational gaps in running the fleet | 3 |
| **P3** | Contained bugs and leftovers | 4 |
| — | Still open from the previous audit | 6 |

**The headline:** the database boundary is sound — routing, connection swapping,
storage suffixing, queue and session pinning are all correct and well tested. The leak
is *above* it, in a shared cache the tenancy layer never reaches.

---

## P0 — Tenant isolation is not actually holding

### TEN-01 · Roles and permissions leak between tenants through a shared cache

**This is the most serious finding, and it is reproducible.**

`spatie/laravel-permission` caches the entire role-to-permission map under a single key,
`spatie.permission.cache`. It resolves its cache through `$cacheManager->store()`
(`vendor/spatie/laravel-permission/src/PermissionRegistrar.php:90`), which is a real
method on the base `CacheManager` — so it bypasses `Stancl\Tenancy\CacheManager::__call()`,
the only thing that would have applied a per-tenant tag. The store it lands on is
`database`, pinned to the **central** connection in `config/cache.php`. One row. Every
tenant.

**Reproduction** (run against the live databases, cleaned up afterwards):

```
[acme]  created role ACME_ONLY (id=6) with permission 'list-dashboard'
[acme]  cache warmed from tenant_acme

[green] roles actually in green's database:  Admin, Product Manager, Super Admin, User
[green] roles the permission layer reports:  Admin, Product Manager, User, ACME_ONLY

>>> CROSS-TENANT LEAK: YES
[green] user 'green@gmail.com' can('list-dashboard') = true
```

A role that exists only in tenant `acme` is live inside tenant `green`, and grants a
`green` user a permission `green`'s own database never gave them.

**Why it is not merely theoretical.** `RoleController::store()` lets any tenant create
roles, so the maps genuinely diverge. Assignments in `model_has_roles` are stored by
**integer id**, and role ids are allocated independently per database — so role id 3 in
one tenant resolves against whatever role id 3 means in the tenant that last warmed the
cache. Whether a given request is right or wrong depends on which tenant touched it
last: `forgetCachedPermissions()` flushes globally on any role edit, and the next
request from any tenant re-warms it with that tenant's data. It is a race, it is
non-deterministic, and it fails in the direction of granting access.

**Fix.** Any one of these closes it; the first is a one-line change:

1. Set `permission.cache.store` to a store that is per-process and never shared
   (`array`) — correct, at the cost of one query per request.
2. Make the cache key tenant-specific: set
   `app(PermissionRegistrar::class)->cacheKey .= '.'.tenant('id')` from a tenancy
   bootstrapper, so each tenant gets its own row.
3. Move the cache to Redis and enable `RedisTenancyBootstrapper` — this also fixes
   `TEN-02`, and is the right answer if this platform is going to cache anything else.

Whichever is chosen, it needs a test that warms the cache in one tenant and asserts the
other cannot see it. Nothing in the current 329 would have caught this.

### TEN-02 · Every `Cache::` call inside a tenant request throws

`CacheTenancyBootstrapper` is enabled, and it swaps the cache manager for
`Stancl\Tenancy\CacheManager`, whose `__call()` routes **every** method through
`->tags()`. The configured store is `database`, and `Illuminate\Cache\DatabaseStore`
does not implement `TaggableStore`.

Observed directly, with tenancy initialised for `acme`:

```
[acme] cache manager class = Stancl\Tenancy\CacheManager
[acme] Cache::put THREW: BadMethodCallException: This cache store does not support tagging.
```

No application code calls `Cache::` today, so nothing is broken right now. What makes
it worth listing at P0 is the shape of the trap: the first person to add caching — the
obvious fix for the dashboard's aggregate queries — gets a 500 in production and a
**green test suite**, because `phpunit.xml` forces `CACHE_STORE=array`, and the array
store *is* taggable.

**Fix.** Switch the cache store to Redis (taggable, and `RedisTenancyBootstrapper` is
already sitting commented out at `config/tenancy.php:42`), or disable
`CacheTenancyBootstrapper` and prefix keys by hand. Either way, stop the test suite from
running on a store with different capabilities than production.

---

## P1 — Configuration that silently does nothing

### TEN-03 · Three `.env` keys are Laravel 10 names that Laravel 11 no longer reads

| `.env` sets | `config/*.php` actually reads | Result |
|---|---|---|
| `BROADCAST_DRIVER=reverb` | `BROADCAST_CONNECTION` → default `'null'` | **Broadcasting is off** |
| `CACHE_DRIVER=database` | `CACHE_STORE` → default `'database'` | Works by coincidence |
| `FILESYSTEM_DRIVER=local` | `FILESYSTEM_DISK` → default `'local'` | Works by coincidence |

The broadcast one changes behaviour: Reverb is installed, credentials are configured,
`laravel-echo` and `pusher-js` are in `package.json`, and `resources/js/bootstrap.js`
builds an Echo client — but server-side the broadcaster is the **null driver**, so every
`broadcast()` would be a silent no-op. The other two happen to match their defaults, so
they mislead rather than break: changing them has no effect.

### TEN-04 · The realtime layer is not tenant-scoped, and will leak when it is switched on

Two independent problems, both dormant only because `TEN-03` has broadcasting disabled:

- **`broadcasting/auth` has no domain constraint and no tenancy middleware.** It is one
  of 16 routes out of 247 without `InitializeTenancy*`, and the only one that touches
  user data. On `acme.example.com/broadcasting/auth`, no tenant is bound, so
  `Auth::user()` resolves the session's user id against the **central** `users` table —
  which still has 4 rows (see `TEN-10`).
- **Channel names are global.** `routes/channels.php` authorises `user.{id}` by
  comparing `$user->id === $id`. User ids restart at 1 in every tenant database, and the
  Reverb app is shared, so `private-user.1` is the same channel for every client on the
  platform.

**Fix before enabling Reverb:** register the broadcast routes inside the tenant group,
and prefix channel names with the tenant id (`tenant.{tenantId}.user.{id}`), asserting
the tenant matches in the authorisation callback.

### TEN-05 · A shared developer account opens every client workspace

`TENANT_DEV_ACCOUNT_ENABLED=true` puts the same email and the same password into every
tenant database as an account owner, and `tenant:dev-account` re-applies it to all of
them. The risk is already written down in `.env` and printed by the command, so this is
a note that it is still switched on, not a discovery:

> the same credentials now open every one of those workspaces. Handing a client their
> database hands over this account's hash too.

Given that "we can hand you your own database" is the *selling point* of this
architecture, this account contradicts the product. The stated alternative — audited
impersonation from the back-office — is the right destination;
`Stancl\Tenancy\Features\UserImpersonation` is available and currently commented out at
`config/tenancy.php:188`.

---

## P2 — Operational gaps in running the fleet

### TEN-06 · Nothing migrates existing tenants when a tenant migration is added

`Jobs\MigrateDatabase` runs once, at tenant creation. After that, a new file in
`database/migrations/tenant/` reaches **new tenants only**. Existing tenants keep the old
schema and start throwing on the first query that touches a new column.

`tenants:migrate` appears nowhere in the repository: not in CI, not in a deploy script,
not in any document. The five live tenants are all at 85/85 right now, so someone has
been running it by hand — which is exactly the practice that fails the first time it is
forgotten. Today's commit (`5c6c109`) added a tenant migration, so this is live risk, not
hypothetical.

The `tenants.schema_version` column exists for precisely this and is **written by nothing
and read by nothing** — declared in `Tenant::getCustomColumns()`, present in the
migration, `NULL` on all five rows.

**Fix.** A deploy step that runs `php artisan tenants:migrate`, plus either a startup
check that refuses to serve a tenant whose schema is behind, or a back-office column
showing drift. The `schema_version` column already reserves the space for it.

### TEN-07 · Provisioning runs inline inside the HTTP request

`Platform\TenantController::store()` calls `Artisan::call('tenant:provision', ...)`
synchronously: create database, run 85 migrations, seed roles, permissions, 271 emission
factors, 190 emission sources and 119 industry templates. In the test suite that path
takes **10–11 seconds** per tenant. Behind a typical 30s PHP-FPM timeout, on a loaded
server, a browser-initiated onboarding will eventually cut out mid-provision.

`TenancyServiceProvider` already flags this — `shouldBeQueued(false)` carries the comment
*"Move to the queue once provisioning is exposed in the UI."* It is now exposed in the
UI. The `provisioning` and `failed` statuses and the `tenant.unavailable` view are already
built, so the asynchronous path is mostly a matter of flipping the flag and having the
controller redirect to a status page.

### TEN-08 · No offboarding, no export, no billing

The tenant lifecycle stops at suspend/reactivate:

- **No delete or archive path.** `TenantDeleted` has a full pipeline wired
  (`DeleteDatabase` → `DeleteTenantStorage`) and **nothing in the application or the CLI
  ever fires it.** The only `$tenant->delete()` in the codebase is the rollback inside
  `ProvisionTenant`. A client asking to be deleted currently has no answer, which is a
  GDPR/DPA problem, not just a missing feature.
- **No data export.** The architecture's promise is "your data is in your own database";
  there is no button or command that gives them a dump of it.
- **No billing at all.** `tenants.plan` is written once at provisioning and read nowhere.
  `past_due` is a status the back-office renders and nothing ever sets. No seat limits,
  no plan enforcement, no metering.
- **No self-serve signup.** Every tenant is created by staff through `/admin`. That may
  be the intended sales motion — worth confirming, because it is the difference between a
  SaaS and a hosted install.

---

## P3 — Contained bugs and leftovers

### TEN-09 · An account owner's company selection is silently cleared by ordinary saves

`app/Http/Middleware/SetCompanyConnection.php:27-35`:

```php
if ($request->has('company_id')) {
    $requestedCompanyId = $request->query('company_id');   // query string only
    if (Auth::check() && Auth::user()->canAccessCompany($requestedCompanyId)) {
        $companyId = $requestedCompanyId;
        $request->session()->put('current_company_id', $companyId);
    }
}
```

`Request::has()` reads `$this->all()` — query string **and request body**.
`$request->query()` reads the query string only. So a POST whose body contains
`company_id` makes the condition true while `$requestedCompanyId` is `null`. For an
account owner, `canAccessCompany(null)` returns `true` unconditionally
(`User::canAccessCompany():121`), so the branch runs and writes `current_company_id = null`
into the session.

`HasCompanyScope` then takes its `is_account_owner` early return and leaves the query
**unscoped** — so the owner's chosen company filter is gone and every company in the
account is pooled together, with nothing on screen saying so.

Reachable from `users/create.blade.php:101`, `users/edit.blade.php:113` and
`sites/index.blade.php:56`, all of which post `company_id` in the body. No cross-tenant
exposure — the tenant database is still the boundary — but the figures an owner reads
after saving a user are silently for a different scope than the one they selected.

**Fix.** Read from the same place the check tests: `$request->input('company_id')`, and
guard on a non-empty value.

### TEN-10 · The central database still holds the entire pre-tenancy application schema

`ghg_emission_saas` carries all 40-odd application tables alongside `tenants`, `domains`,
`platform_users`, `sessions`, `cache` and `jobs` — including 271 `emission_factors`, 489
`audit_logs`, 93 `permissions`, 4 `roles` and 4 `users` left over from before the split.

The command layer is well defended (`App\Console\Concerns\RequiresTenant` guards all seven
data-touching commands, and `tenants:each` is the documented way to run them), so this is
not currently causing wrong writes. What it does is remove the safety net: with those
tables present, any future code path that runs outside tenant context reads and writes
plausible-looking data instead of failing with "table not found". It also leaves real
user rows reachable through `broadcasting/auth` (`TEN-04`).

**Fix.** Drop the application tables from central, and keep the central migration set to
what actually belongs there. The absence of a table is the cheapest possible guard.

### TEN-11 · Every new tenant is born with 485 rows of audit trail that are not theirs

`TenantDatabaseSeeder` runs with the `Auditable` trait active, so seeding writes an audit
entry per seeded row: 271 `EmissionFactor` creates, 190 + 13 `EmissionSource` creates and
updates, 11 `EioFactor` creates. A brand-new client opens their change history and finds
485 entries with `user_id = NULL` before they have done anything.

Since the audit trail is the artefact an ISO 14064-3 assurer reads, its first 485 rows
should not be seeding noise. Suppress auditing during seeding (`Model::withoutEvents()`
or a flag on the trait).

### TEN-12 · CI does not run on this branch

`.github/workflows/tests.yml` triggers on `push` to `main` and `boundary` only. Pull
requests are covered, so this is narrow — but every direct push to `saas-tenancy`, which
is where all the tenancy work has landed, ran no tests. Add the branch, or trigger on all
pushes.

Also still outstanding from the previous audit: **there is no README**. A new developer
has no path from clone to running application, and this branch has made that materially
harder — wildcard subdomains, a hosts file, per-tenant migrations and a separate
back-office login are now all prerequisites, documented only in `.env` comments.

---

## Still open from `CODEBASE_AUDIT.md`

Most of that audit has been worked through — the import lock, enrichment, server-side
factor resolution, chunked reading, import id allocation, the `@json()` crash, audit-log
authorisation, the junk files and the `data_source` mismatch are all closed, and the suite
has grown from 132 tests to 329. What remains:

### GHG-04 · The factor merge was planned and reconciled but never executed

The conflicts are resolved — `docs/FACTOR_RECONCILIATION.md` now reports **0
disagreements** — but step 2 of the plan, compiling the config catalogue into the
database, has not run. Measured in `tenant_acme`:

| Column | Rows populated (of 271) |
|---|---|
| `dataset_name` | **0** |
| `dataset_version` | **0** |
| `co2_factor` | **0** |
| `net_calorific_value` | **0** |
| `country_id` | 3 |

So the two catalogues still stand side by side: 238 built-in entries have no database
row, 207 database rows have no built-in counterpart. Scope 1/2 records get provenance as
a **config-catalogue label string** (`"Built-in Scope 1 catalogue v1"`), not an
`emission_factor_id` foreign key — and that version is a hand-maintained constant
(`BuiltInFactorCatalog::VERSION`) with nothing enforcing that it is bumped when the
numbers change. Editing `config/scope1_sources.php` therefore restates every historical
figure derived from it, with no way to tell which revision produced a stored number.

This is worse under tenancy than it was before: the empty catalogue is now seeded into
**every** client database, so the fix has to be a migration across the fleet rather than
one table.

### GHG-17 / GHG-18 · MRV and country coverage, unchanged

All four MRV tables are empty in every tenant — MRV phase 1 remains unusable because it
needs exactly the decomposed factor components GHG-04 would populate. Country-specific
factors are still 3 of 271 against 5 seeded countries, so grid electricity is effectively
generic for every client outside the default region.

### GHG-15 / GHG-16 / GHG-19 / GHG-20 · Feature work, unchanged

Five AI features still "Not started" per `AI_FEATURES.md`. `SupplierMatchingService` is
now consumed by `DocumentEmissionExtractor` (GHG-16 partially closed). `ProcessExportJob`
still handles only pdf/excel/csv while `pptxgenjs` remains a shipped dependency. The Data
Source page still renders `data_source/coming_soon.blade.php`.

---

## What was verified as sound

Worth recording so it is not re-audited:

- **The database boundary itself.** Subdomain identification, connection swapping,
  `PreventAccessFromCentralDomains`, and `EnsureTenantIsActive` (correctly prioritised
  ahead of `Authenticate`) are all right, and covered by `TenantSubdomainRoutingTest` and
  `SuspendedTenantAccessTest`.
- **Session, queue and cache connections are pinned central** rather than left `null`,
  which is the standard way this architecture breaks. `config/session.php:85`,
  `config/queue.php:45` and `config/cache.php:47` each pin explicitly and explain why.
- **The session cookie is host-only** (`SESSION_DOMAIN` unset), so a session on one
  subdomain is not a session on another. Worth a comment in `.env`: setting
  `SESSION_DOMAIN=.example.com` — a natural thing to reach for later — would share one
  cookie across every tenant, and since the session payload holds only a numeric user id
  and the `sessions` table is central, that single change is a cross-tenant login bypass.
- **Filesystem isolation.** `FilesystemTenancyBootstrapper` with `root_override`,
  `asset_helper_tenancy` deliberately false, storage directories created and deleted by
  their own jobs, and `/storage/tenant*` gitignored.
- **Command safety.** All seven data-touching commands refuse to run without a tenant.
- **Guard separation.** Platform staff authenticate on their own guard against their own
  table, so a client's user table can never hold an account with reach beyond that client.
- **Mass assignment on `User`.** `$fillable` is tight and `is_account_owner` is stripped
  from input on both create and update.
- **`.env` has never been committed**, and per-tenant storage is excluded.

---

## Suggested order

1. **`TEN-01`** — one line for the safe version, and it is an active authorisation leak.
2. **`TEN-03`** — rename three keys; it tells you whether broadcasting was ever meant to
   be on, which decides how urgent `TEN-04` is.
3. **`TEN-09`** — one line, contained, currently producing wrong numbers on screen.
4. **`TEN-06`** — a deploy step, before the next tenant migration is written.
5. **`TEN-02`** and **`TEN-04`** together — both are answered by moving the cache to Redis
   and scoping the broadcast layer, and both are cheaper now than after something depends
   on them.
6. **`TEN-05`**, **`TEN-07`**, **`TEN-08`** — the commercial and operational shape of the
   product, which needs a decision before an estimate.

`GHG-04` is unchanged in size but has grown in blast radius: it is now a fleet-wide data
migration, and reconciling a factor differently from what a past entry assumed remains a
**restatement decision** for whoever signs off the inventory, not an engineering task.
