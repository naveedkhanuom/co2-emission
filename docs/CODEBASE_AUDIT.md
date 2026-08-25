# Codebase Audit — Findings & Priority

> **What this is:** twenty-three findings from a full read of the platform, ranked by
> what each one does to the **integrity of the numbers**, not by how hard it is to fix.
> Every item was verified against the code or the live database — nothing here is
> inferred from naming or assumed from a pattern.
>
> **Why that ordering:** this platform's output goes to an assurer (ISO 14064-3 /
> CSRD). A wrong figure that propagates consistently through every report is worse
> than a crash, because nobody notices it.

- **Date:** 2026-08-25
- **Branch:** `boundary`
- **Scope:** 34 models · 45 controllers · 85 migrations · 132 tests passing
- **Web version:** https://claude.ai/code/artifact/09cc5902-e90e-45b3-bb0a-8be7fd2e6897

Findings carry stable IDs (`GHG-01`…`GHG-23`) so they can be referenced from tickets.

---

## Summary

| Band | Theme | Count | Effort |
|---|---|---|---|
| **P0** | The numbers can't be trusted | 4 | 10–15 days |
| **P1** | Governance & exposure | 4 | 5–8 days |
| **P2** | Contained bugs | 6 | 1.5 days |
| **P3** | Missing features | 7 | 8–12 weeks |
| **P4** | Hygiene | 2 | 6–9 days |

### Start here — six fixes, under one day total

`GHG-13` (15 min) · `GHG-10` (30 min) · `GHG-23` (5 min) · `GHG-06` (1 h) · `GHG-09` (1 h) · `GHG-07` (2 h)

None need design decisions or touch historical data. Clearing them shrinks the list
by a quarter before the real work starts.

### Suggested order

1. The six quick wins above.
2. `GHG-01` and `GHG-02` — both contained, both close governance holes.
3. `GHG-03` — its factor resolver is the seam `GHG-04` later plugs into.
4. `GHG-04`.

`GHG-08` shares no code with the others and can run in parallel.

---

## P0 — The numbers can't be trusted

**10–15 days**

### GHG-01 · Excel import writes into locked reporting periods — 0.5 d

`EmissionRecordController` blocks writes to a locked year in five separate places.
`app/Imports/EmissionsImport.php` has **no lock check at all** — and with `overwrite`
enabled it calls `updateOrCreate`, so a spreadsheet can silently rewrite a finalised,
signed-off inventory.

This defeats the period-locking feature entirely, and the damage is invisible after
the fact. Cheapest P0 to close.

### GHG-02 · Import skips the enrichment service — 1 d

The import runs `EmissionFigureVerifier` but never `EmissionEnrichmentService`.
Imported rows get no GWP stamp, no factor lock, no per-gas split, no Scope 2 dual
reporting.

**Evidence:** 22 of 58 records have `gwp_version = NULL`. Those figures state no GWP
basis, which CSRD/ESRS E1 and CDP both require.

### GHG-03 · Scope 1/2/3 entry stores no emission factor, so its figures are never verified — 2–3 d

The entry pages compute CO₂e entirely in the browser and post only the result.
`EmissionFigureVerifier` can only check `activity × factor`; with no factor it returns
`null` and the client's number passes through unchallenged.

**Evidence:** 19 of 58 records have no `emission_factor`; 24 have no
`emission_factor_id`. The provenance chain does not exist for them.

**Approach.** Resolve the factor **server-side** from the same catalogue the browser
uses, rather than trusting a posted one — otherwise both numbers come from the same
untrusted client and a stale or tampered bundle passes cleanly. The per-unit factor is
derivable from the config row alone:

| Path | tCO₂e per unit |
|---|---|
| Scope 1 combustion | `(co2 + ncv × ch4 × GWP_CH4 + ncv × n2o × GWP_N2O) / 1000` |
| Scope 1 fugitive | `gwp / 1000` (or `gwpM3` for m³) |
| Scope 2 | `(kWh-per-unit × ef) / 1000` |

Pull the GWPs from `App\Support\Gwp`, not the `GWP_CH4 => 28` constants at the top of
the config — same values today, but re-basing to AR6 then updates both together.

Prerequisites: the `activity_unit` column (added 2026-08-25) is the enabling piece —
without it the server cannot tell kg from tonnes. Scope 2 additionally needs the
selected grid region and any custom EF override posted, since both are currently
client-only state.

### GHG-04 · Two emission-factor libraries that already disagree — 6–10 d

Manual Entry uses the `emission_factors` table (271 rows). The Scope 1/2 entry pages
use `config/scope1_sources.php` and `config/scope2_sources.php`, shipped to the browser
as JSON.

Only **11 of 271** DB rows match a config source and unit by name, and **6 of those 11
disagree by more than 1%**:

| Source | Unit | Database | Config | Δ |
|---|---|---:|---:|---:|
| Coal Combustion | kg | 0.003260 | 0.002431 | +34.1% |
| Fire Suppression (HFCs) | kg | 1.430000 | 3.220000 | −55.6% |
| Biomass Combustion | kg | 0.000000 | 0.000030 | −100% |
| C2F6 (PFC-116) | kg | 12.400000 | 12.200000 | +1.6% |

Same fuel, same unit, a different answer depending on which page it was entered on.
These are accounting decisions — which coal rank, which refrigerant — not typos.

**Important correction.** The DB table's versioning columns are **empty on all 271
rows**: no `dataset_name`, no `dataset_version`, no `co2_factor`, no NCV. It is
versioned in schema only. The config file is the better-documented source (IPCC
references, gas decomposition, NCV per unit), so the merge runs **config → database**,
not the reverse.

**Approach.**

1. Give every config source a **stable key** (`stationary.coal.bituminous`), not a
   display name — 11/271 name matches shows names are unusable as identity.
2. A `factors:compile` command turns each config `(source, unit)` pair into an
   `emission_factors` row, populating the currently-empty `co2_factor`, `ch4_factor`,
   `n2o_factor`, `net_calorific_value`, `dataset_name`, `dataset_version`,
   `gwp_version`, `is_active`, `valid_from`. The 23 grid factors go in with
   `region` / `country_id`. This also unlocks the MRV layer, which needs exactly that
   decomposition and currently has zero rows to work with.
3. **Reconcile the conflicts by hand and record the decision.** Auto-picking a side
   silently restates historical emissions.
4. The entry pages fetch factors from an **endpoint**, not a config dump — same JSON
   shape, but every number now carries an `emission_factor_id` the form posts back.
   This closes `GHG-03` and `GHG-04` together.
5. **Version on write, never update in place.** A factor change writes a new row with a
   new `dataset_version`; the old row gets `is_active = false` and a `valid_to`.
   Historical records keep pointing at the exact row that produced them.

Keep the config file as the **build input**, not a runtime source: factor changes stay
reviewable git diffs, while the database provides validity windows and per-record
foreign keys.

---

## P1 — Governance & exposure

**5–8 days**

### GHG-05 · Audit trail covers 3 models out of 34 — 1–2 d

Only `EmissionRecord`, `BoundaryAssessment` and `BoundaryItem` use the `Auditable`
trait. Not `EmissionFactor` — changing a factor silently restates every report built on
it. Not `ReportingPeriod`, whose lock and unlock are the core governance actions. Not
`User`, `Company` or `Target`.

### GHG-06 · `APP_DEBUG=true` and `APP_ENV=local` in the live `.env` — 1 h

Error pages expose full filesystem paths and vendor internals. Harmless locally; a
disclosure issue the moment this config reaches a server.

### GHG-07 · Any authenticated user can read the audit log — 2 h

`AuditLogController` is tenant-scoped correctly but has no role check, so every user
can see the full change history of who altered which figure. Normally restricted to
admins and auditors.

### GHG-08 · Facility and department are name strings, not foreign keys — 3–5 d

Both are stored as free text and filtered by resolving an id back to its name
(`HomeController::index`). **Renaming a facility or department orphans every historical
record filed under the old name** — no migration, no warning. Gets more expensive the
longer it waits.

---

## P2 — Contained bugs

**1.5 days total**

### GHG-09 · Editing a record can leave a stale activity unit — 1 h

`EmissionRecordController::update()` writes `activity_data` without `activity_unit`.
Change a quantity through Manual Entry and the unit no longer matches — and Manual
Entry has no unit field to correct it with.

### GHG-10 · `data_source` accepts different values on different paths — 30 m

The bulk-entries path allows `manual, import, api`; every single-record path also
allows `meter, invoice, estimate`. One field, two rules.

### GHG-11 · Import IDs can collide — 2–3 h

`ImportHistory::generateImportId()` reads the latest row, string-parses `IMP-0042` and
increments. Two concurrent imports get the same id, and one malformed id resets the
sequence to 1.

### GHG-12 · Import loads the whole spreadsheet into memory — 0.5 d

`EmissionsImport` implements only `ToModel` and `WithHeadingRow` — no
`WithChunkReading`, no `WithValidation`. Large files exhaust memory instead of failing
cleanly.

### GHG-13 · A latent `@json()` crash on the dashboard — 15 m

`resources/views/home.blade.php` passes an inline array literal to `@json()`. Blade's
`CompilesJson::compileJson()` splits its argument on commas and keeps only the first
three parts, so this compiles to valid PHP **only by coincidence** — exactly three
elements and no trailing comma. Add a fourth scope and the dashboard dies. The same
pattern took down the GHG Protocol report on 2026-08-25.

Fix: build the array in a `@php` block and pass a single variable.

### GHG-14 · Scope 2 energy conversion keys on a display label — 1 h

`toKwh()` matches on the visible label (`label === 'MWh'`) rather than the unit key.
All eight current labels are covered, so nothing is broken today — but edit a label or
add a unit and it silently falls through to treating the value as kWh. For GJ that is a
277× undercount with no error.

---

## P3 — Missing features

**8–12 weeks**

### GHG-15 · Five AI features unstarted — 2–4 w

Per `AI_FEATURES.md`'s own status table: disclosure narrative generator, reduction
recommendations, factor-update alerts, SBTi validation, report summarizer.

### GHG-16 · Supplier survey parsing built but not wired — 2–3 d

`SupplierMatchingService` exists and is functional; no controller calls it.

### GHG-17 · MRV phase 2 — 3–4 w

Measurement-based (CEMS) numeric path, fall-back uncertainty automation, EU-ETS export
variant. Note that phase 1 is currently unusable in practice — it needs the decomposed
factor components that `GHG-04` would populate.

### GHG-18 · Country-specific factors barely exist — 1–2 w

Only **3 of 271** factors carry a `country_id`, against 5 seeded countries. Grid
electricity factors are effectively generic, which materially affects Scope 2 accuracy
for every tenant outside the default region.

### GHG-19 · PPTX and PNG export half-built — 3–5 d

`pptxgenjs` is a shipped dependency, but `ReportController::storeExportJob()` explicitly
rejects those formats because `ProcessExportJob` never implemented them. Either finish
it or drop the dependency.

### GHG-20 · Data Source page is a stub — unscoped

Routed, linked in the sidebar, renders "Coming Soon". Needs a scope decision before it
can be estimated.

### GHG-21 · No README or setup documentation — 0.5 d

The planning documents are excellent, but nothing tells a new developer how to install,
seed, or run the application.

---

## P4 — Hygiene

**6–9 days**

### GHG-22 · Test coverage is concentrated in one feature — 5–8 d

132 tests pass, but 67 are Boundary Advisor and 54 are calculation units. There are **no
tests** for the Scope 1/2/3 entry controllers, the Excel import, the GHG Protocol or
disclosure reports, analytics, suppliers and surveys, roles and permissions, or the AI
services.

Signal worth noting: every defect reported by hand on 2026-08-25 — four chart bugs, the
missing department field — sat in that untested region.

### GHG-23 · Three junk files in the repository root — 5 m

`null`, `toArray())` and `email}` — captured tinker parse errors from mis-quoted shell
commands.

---

## What was verified as sound

Worth recording so it isn't re-audited:

- **Multi-tenancy.** All 20 `withoutGlobalScope` call sites re-scope by `company_id`
  correctly. The fail-closed `whereRaw('1 = 0')` guard in `HasCompanyScope` is good
  design — an authenticated user with no bound company is denied rather than leaking.
- **SQL injection.** The `DB::raw("{$col}")` sites in `EmissionAnalyticsService` all
  draw from `match()` allow-lists with a `default`.
- **Mass assignment.** No `$request->all()` reaching `create()` or `update()` anywhere.
- **File uploads.** Mime- and size-validated across every upload path.
- **Authorization on sensitive settings.** `GeneralSettingController` (super-admin
  closure) and `CompanySwitcherController` (`canAccessCompany`) are both correctly
  gated — they only look unguarded because they don't use `permission:` middleware.

---

## Fixed on 2026-08-25

- Scope Finder moved to the top of the Data Entry sidebar section.
- Department field added to the Scope 1/2/3 entry forms — records created there were
  saving `department = NULL` and vanishing from the dashboard filter.
- Monthly Trend, Scope 1 Categories and Facility Comparison y-axes given explicit
  scales and formatters — a degenerate tick interval was rendering
  `15000.000000000000`.
- Phantom `series-4` slice removed from the Emissions by Scope donut: `number_format()`
  output was being interpolated into a JS array literal, so its thousands separator
  split one value into two and shifted every label onto the wrong scope.
- `activity_unit` column added and wired through Scope 1/2 entry — the unit was
  previously discarded after the CO₂e calculation.

---

## On the estimates

Ranges assume one developer already familiar with the codebase, and include tests but
not code review or deployment.

`GHG-04` and `GHG-08` both touch historical data. Resolving a factor conflict
differently from what a past entry assumed means that record's stated figure no longer
matches its active factor — that is a **restatement decision** for whoever signs off the
inventory, not an engineering task, and it is not in the estimate.
