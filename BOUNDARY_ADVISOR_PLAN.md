# AI Boundary Advisor — Implementation Plan

> **Goal:** Let a company describe what it *does* in plain language, answer a few
> clarifying questions, and receive a tailored, auditable list of exactly which
> emission parameters it must measure — scope by scope, including a justified
> relevance screening of all 15 Scope 3 categories.
>
> **The problem it solves:** the platform can calculate anything the user enters,
> but a construction firm, a car-rental company or a hospital does not know
> *what to enter*. They do not know their own inventory boundary. Today that is a
> blank screen; this feature turns it into a short, relevant, actionable checklist.
>
> **Principle: everything here is additive.** No existing entry flow, calculation
> or report changes. A company that never runs the advisor behaves exactly as today.

- **Date:** 2026-08-05
- **Branch:** `boundary`
- **AI provider:** Anthropic Claude via `app/Services/AI/ClaudeService.php`

---

## 1. What this is, in GHG Protocol terms

Two standard concepts. We already have half of one.

| Concept | Meaning | Status |
|---|---|---|
| **Organizational boundary** | Which entities/facilities are in the inventory (equity share / financial control / operational control) | **Done** — `config/boundary.php` + the `consolidation_approach` company setting, captured in onboarding |
| **Operational boundary** | Which *emission sources* count inside those entities, split by scope, plus a relevance screening of all 15 Scope 3 categories **with justified exclusions** | **Missing — this plan** |

This framing matters. The output is not "AI hints"; it is a deliverable the
company already owes a regulator:

- The **GHG Protocol Scope 3 Standard** requires screening all 15 categories and
  justifying any that are excluded.
- **CSRD / ESRS E1** requires a description of the reporting boundary.
- An **assurer** will ask *why* a source was left out, and expect a reason on record.

So the feature produces a **Boundary Statement** — an exportable, versioned,
audited artefact — not just a to-do list.

---

## 2. Core design principle

> **The AI ranks and justifies. The catalogue constrains.**

Claude never invents an emission source, a factor or a category. It selects from
data we already hold — `IndustryEmissionTemplate`, `EmissionSource`, the 15
`Scope3Category` rows — and every id it returns is validated against the database
before it is stored. Anything unmatched is dropped or flagged `source = manual`
for human review.

This is the same discipline `ScopeClassificationService` already uses when it maps
a returned `scope3_category_number` back through `firstWhere('sort_order', …)`
rather than trusting the model's text. Follow it exactly, including the
deterministic fallback so the feature still works with no API key configured.

---

## 3. How it works (user flow)

```
Step 1  Profile      →  Step 2  Questions  →  Step 3  Checklist  →  Step 4  Statement
        (form)                (max 6-8)            (accept/exclude)     (export + activate)
```

**Step 1 — Profile.** The user picks their industry and, more importantly, writes
one or two plain sentences: *"We rent cars and vans to consumers and businesses
from 12 branches."* Free text is the primary AI input; the coarse `industry_type`
enum is only a hint (see §9).

**Step 2 — Clarifying questions.** 6–8 questions, every one multiple-choice with a
"Not sure" option. These are the questions that actually change the answer:

- Construction: *"Do you buy concrete and steel directly, or does your subcontractor?"*
- Car rental: *"Are the kilometres your customers drive your responsibility?"* — this
  single question decides Scope 1 vs Scope 3 category 13, the classic misclassification.
- Healthcare: *"Do you use anaesthetic gases or medical refrigerants on site?"*
- Any: *"Do you own your buildings or lease them?"* — decides Scope 1/2 vs Scope 3 cat. 8.

Never free-text. The users are ordinary facility, finance and ops staff, not
carbon accountants — as the `OnboardingController` docblock already states.

**Step 3 — Recommendation checklist.** Grouped by scope. Each row shows:
a plain-language name, **why it applies to this company**, **where to find the
data**, a materiality badge, and Include / Exclude / Not sure yet. Excluding
forces a written reason — that reason is the audit trail.

**Step 4 — Boundary Statement.** Activating the assessment freezes it, supersedes
last year's, and produces the exportable statement plus the Scope 3 relevance table.

---

## 4. Data model (3 migrations, all additive)

### 4.1 `companies` — a richer profile

The existing `industry_type` enum is too coarse for boundary work: a car-rental
firm and a container shipping line are both `transportation` with completely
different boundaries.

```
ALTER companies ADD:
  business_description  text null     -- free text; the primary AI input
  sub_industry          string null
  isic_code             string null   -- optional standard classification
```

### 4.2 New table: `boundary_assessments`

One per company per reporting year, versioned so re-assessments are traceable.

```
boundary_assessments
  id, company_id, reporting_year
  status              enum(draft, active, superseded)   default draft
  version             int
  profile_snapshot    json    -- the answers as given, frozen
  questions           json    -- the Q&A transcript
  summary             text    -- plain-language boundary statement
  model               string null
  prompt_version      string null
  confidence          decimal null
  generated_at        timestamp null
  completed_by        fk users null
  completed_at        timestamp null
  unique (company_id, reporting_year, version)
```

### 4.3 New table: `boundary_items`

The checklist itself — one row per candidate parameter. This is the heart of the
feature, because each item is individually decided, evidenced and tracked for coverage.

```
boundary_items
  id, company_id, boundary_assessment_id
  scope                 tinyint            -- 1 | 2 | 3
  scope3_category_id    fk null            -- validated against scope3_categories
  emission_source_id    fk null            -- matched global EmissionSource, if any
  suggested_name        string             -- "Rental fleet fuel (customer use)"
  suggested_unit        string null        -- litres, kWh, tonne-km
  materiality           enum(high, medium, low)
  relevance             enum(relevant, not_relevant, unknown)
  decision              enum(pending, included, excluded, deferred)
  exclusion_reason      text null          -- REQUIRED when decision = excluded
  rationale             text               -- why it applies to THIS company
  data_hint             text               -- where the number lives (see §7)
  typical_share_pct     decimal null       -- rough % of footprint, for ordering
  confidence            decimal
  source                enum(ai, template, manual)
  accepted_by, accepted_at
```

> **Note:** `EmissionSource` is global (`name`, `scope`, `description` — no
> `company_id`). Boundary items must therefore be their own company-scoped rows
> that *reference* a global source when one matches — never create per-tenant
> `EmissionSource` records.

Both new models use `HasCompanyScope` and `Auditable`. Boundary decisions are
exactly the kind of claim an assurer will ask you to evidence.

---

## 5. Services (`app/Services/Boundary/`)

```
app/Services/Boundary/BoundaryProfileService.php          // normalise profile → AI payload
app/Services/Boundary/BoundaryInterviewService.php        // generate clarifying questions
app/Services/Boundary/BoundaryRecommendationService.php   // produce + validate the checklist
app/Services/Boundary/BoundaryCoverageService.php         // included items vs actual data
config/boundary_questions.php                             // deterministic question bank
```

### 5.1 `BoundaryInterviewService`

Two layers, so it degrades gracefully:

1. A **deterministic question bank** per industry in `config/boundary_questions.php`.
2. Claude adds up to ~4 company-specific questions via `ClaudeService::json()`.

Hard cap the total at 8. Every question carries pre-defined options.

### 5.2 `BoundaryRecommendationService`

The main call. `PROMPT_VERSION = 'boundary-advisor-v1'`. The prompt carries:

1. company profile + interview answers,
2. the **candidate catalogue** — `IndustryEmissionTemplate::getByIndustry()`,
   global `EmissionSource` names, and all 15 `Scope3Category` rows **with their ids**,
3. the rule that makes the output audit-grade:

> For **every one** of the 15 Scope 3 categories, return `relevant`,
> `not_relevant` or `unknown` with a one-line reason. Silent omission is not allowed.

**Validation before anything is stored** — mirroring `ScopeClassificationService`:

- drop any `emission_source_id` / `scope3_category_id` not present in the database,
- force `scope ∈ {1,2,3}`; a Scope 3 item must resolve to a real category,
- clamp `confidence` to 0.0–1.0,
- require a reason on anything marked `not_relevant`,
- record `model`, `prompt_version`, `confidence`, `generated_at`.

### 5.3 Fallback with no API key

`IndustryEmissionTemplate::getByIndustry()` plus the `ACTIVITY_SCOPES` map already
in `OnboardingController` yields a usable deterministic baseline at lower
confidence, with `source = template`. Same graceful degradation as the existing
scope classifier — the feature never hard-fails on a missing key.

---

## 6. Controller, routes, UI

```
app/Http/Controllers/BoundaryController.php
resources/views/boundary/{index,profile,questions,checklist,statement}.blade.php
```

```php
GET    /boundary                     BoundaryController@index      // current assessment
POST   /boundary/start               @start        // capture profile → questions
POST   /boundary/answers             @answers      // save answers → run recommendation
PATCH  /boundary/items/{item}        @decide       // include / exclude (+reason) / defer
POST   /boundary/{assessment}/activate @activate   // draft → active, supersede previous
GET    /boundary/{assessment}/export @export       // Boundary Statement (PDF / Excel)
```

Guard with a new `boundary` permission in the existing spatie set. Blade +
Bootstrap 5, following existing view conventions; no new frontend dependencies.

---

## 7. The part that decides whether this feature succeeds

A checklist alone converts *"I don't know what to measure"* into *"I know what to
measure and still can't get the data."* A construction firm told it needs
purchased concrete learns something true and immediately useless — the number
lives with a subcontractor.

**Every included item must end in a button, not a bullet.** `data_hint` drives a
concrete next action, reusing what already exists:

| Item looks like | Action offered | Existing feature reused |
|---|---|---|
| Electricity, gas, fuel bills | **Upload bill** | `BillOCRController` + `BillDataExtractor` |
| Purchased goods, materials | **Ask this supplier** | supplier portal + `SupplierSurveyController` |
| Fleet fuel | **Import CSV** | `EmissionImportController` |
| Anything with no data yet | **Estimate from spend for now** | `EioFactor` spend-based path |

The spend-based escape hatch matters most: it lets a user close a gap *today* with
data they already have and refine later. That is how real inventories get built.

---

## 8. Integration points

- **Onboarding** — offer "Let AI work out what you need to measure" as a final
  step; the wizard already collects industry, activities, sites and base year.
- **Entry forms** — sort/filter the Scope 1/2/3 source pickers so *included*
  boundary items come first. The blank screen becomes a short relevant list.
- **Data Health** — new **boundary coverage** metric:
  `included items with ≥1 record this year ÷ total included`.
- **Disclosure reports** — feed the statement and the exclusion justifications
  into `DisclosureReportService` (ESRS E1 boundary description; the Scope 3
  category-by-category relevance table).
- **Year-over-year** — diff this year's assessment against last year's; a changed
  boundary raises a base-year recalculation flag.
- **MRV layer** — a facility with `mrv_enabled` can carry its boundary items
  through to source streams instead of re-entering them.

---

## 9. Prerequisite — expand the template seeder first

`IndustryEmissionTemplateSeeder` currently covers **9 of the 16 industries**
(~46 rows): `agriculture`, `construction`, `energy`, `food_beverage`,
`manufacturing`, `other`, `retail`, `technology`, `transportation`.

Missing: **`healthcare`, `hospitality`, `mining`, `chemical`, `textile`,
`education`, `finance`.**

That seeder is both the AI's candidate catalogue *and* the no-API-key fallback, so
both paths are thin for exactly the industries this feature is meant to serve.
Expand it before building on top. Also consider a `sub_industry` column on
`industry_emission_templates` so "car rental" and "shipping" can differ within
`transportation`.

---

## 10. Benefits

### For the user
- **Removes the hardest blocker in corporate carbon accounting.** Boundary-setting
  is where most GHG projects stall. The user goes from a blank screen to a short,
  relevant, prioritised list in minutes.
- **No expertise required.** Plain-language questions in, plain-language reasons
  out. Nobody has to know what "Scope 3 category 13" means.
- **Prioritised, not exhaustive.** `materiality` and `typical_share_pct` mean users
  chase the 20% of sources that are 80% of the footprint first.
- **Every item is actionable** (§7) — the checklist becomes a work queue.

### For compliance and audit
- **Satisfies a mandatory requirement** — the Scope 3 Standard's all-15-category
  screening with justified exclusions, produced as a by-product of setup.
- **Defensible exclusions.** Every "not relevant" carries a written reason, a
  model, a prompt version, a confidence and the person who accepted it.
- **Versioned per year**, so a boundary change is visible and can trigger base-year
  recalculation — exactly what an assurer looks for.
- Feeds the ESRS E1 boundary narrative directly.

### For the product
- **Fixes activation.** It sits upstream of all ~45 controllers of accounting
  machinery that currently assume the user already knows what to enter. It is a
  bigger lever than the existing AI scope classifier, which only helps classify an
  entry the user already thought of.
- **Boundary coverage is a retention metric.** *"14 of 19 boundary items have data
  for 2026"* is the single number that pulls users back week after week and gives
  the Data Health page a reason to exist.
- **Improves data quality upstream**, before wrong or missing sources reach a
  report — cheaper than catching them in review.
- **Demo-friendly**: "tell it you're a cement factory" is a 60-second sales moment.

### Honest limits
- **Not a differentiator.** Watershed, Persefoni, Normative and Greenly all ship
  some form of materiality screening. Build this to fix activation, not to win
  deals — the genuine moat remains the EAD/UAE MRV export (`MRV_EAD_PLAN.md`).
- **Value decays after first use.** It is touched roughly once a year. Cap the
  investment at phases 1–4 and put the saved effort into §7 and coverage.

---

## 11. Risks and mitigations

| Risk | Mitigation |
|---|---|
| **The users least able to judge the output are the ones using it.** AI output is fluent and confident whether right or wrong. | Constrain to the curated catalogue (§2); show confidence and rationale on every row; UI copy says "a starting point for your review" and never implies the result is compliant or assured. |
| Model invents a source, factor or category | Validate every id against the database before storing; unmatched → dropped or `source = manual` (§5.2) |
| Generic, boilerplate recommendations | Free-text `business_description` as primary input, not the coarse industry enum; clarifying questions that actually change the answer (§3) |
| Checklist becomes a dead end | Every item ends in an action (§7) |
| Cost / latency | One or two calls per assessment, roughly once a year — negligible. Cache the catalogue portion of the prompt. |
| Scope creep into a chat product | Hard cap of 8 multiple-choice questions; no open-ended conversation |
| Tenant leakage | `HasCompanyScope` on both models; the prompt carries only this company's profile |

---

## 12. Phasing

| Phase | Deliverable | Effort |
|---|---|---|
| **0** | Expand `IndustryEmissionTemplateSeeder` to all 16 industries (+ `sub_industry`) | S |
| **1** | Migrations + `BoundaryAssessment` / `BoundaryItem` models + company profile fields | S |
| **2** | `config/boundary_questions.php` + `BoundaryInterviewService` (deterministic only) | S–M |
| **3** | `BoundaryRecommendationService` + Claude prompt + strict validation + unit tests | M |
| **4** | Wizard UI + checklist with accept / exclude-with-reason | M |
| **5** | Action handoff (§7) + Data Health coverage metric | M |
| **6** | Boundary Statement export + disclosure-report wiring | M |
| **7** | Annual re-assessment + year-on-year diff + base-year recalculation flag | S–M |

Phases 0–4 deliver the user-visible feature. Phases 5–7 are what turn it from an
onboarding wizard into the spine of the product.

---

## 13. Tests to write early

- **Car rental → customer-driven kilometres must resolve to Scope 3 (cat. 13 or 11),
  never Scope 1.** The classic misclassification; a good regression guard on any
  prompt change.
- Leased building → Scope 3 cat. 8, not Scope 1/2, when the lease answer says so.
- All 15 Scope 3 categories are always present in the result, each with a relevance
  and a reason.
- A hallucinated `emission_source_id` is dropped, not stored.
- With no API key configured, the template fallback still returns a usable
  checklist at `source = template`.
- Excluding an item without a reason is rejected.

---

## 14. What does NOT change (guarantees)

- The global `CO₂e = Activity × Factor` calculation is untouched.
- No existing entry, import, analytics or report flow changes behaviour.
- Companies that never run the advisor are unaffected; all new columns are nullable.
- The advisor only ever produces **drafts a human accepts** — it never creates,
  edits or deletes an emission record.
- Works with no Anthropic API key, at reduced quality (§5.3).
