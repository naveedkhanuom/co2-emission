# AI Features — GHG Emissions Platform

A catalogue of AI capabilities for this platform, grounded in the **existing
codebase**. Use it to decide what to build and in what order.

- **Written:** 2026-06-18 · **Last reviewed:** 2026-08-05
- **AI provider:** Anthropic Claude via `app/Services/AI/ClaudeService.php`

> **Status note (2026-08-05).** This document was written as a plan ("nothing
> here is implemented yet"). That is no longer true — most of Tier 1 and part of
> Tier 2 have shipped. Current state:
>
> | Feature | Status | Where |
> |---|---|---|
> | 1. AI Scope Classifier | **Shipped** | `ScopeClassificationService` + `ScopeClassifierController` |
> | 2. Smart Emission-Factor Matching | **Shipped** | `FactorMatchingService` |
> | 3. Supplier Survey Parsing | **Service only** — `SupplierMatchingService` exists, not wired to a controller |
> | 4. Anomaly & Data-Quality Detection | **Shipped** | `AnomalyDetectionService` + `ScanAnomalies` command + `AnomalyAlert` |
> | 5. Ask-Your-Data Assistant | **Shipped** | `AskYourDataService` + `AskController` |
> | 6. Disclosure Narrative Generator | Not started | — |
> | 7. Reduction Recommendations | Not started | — |
> | 8–9. Factor updates / SBTi | Not started | — |
> | 10. Bulk Document Ingestion | **Shipped** | `DocumentEmissionExtractor` + `DocumentExtractionController`, `AiExtraction` |
> | 11. Report Summarizer | Not started | — |
>
> Also shipped, and **not** in the original list: **natural-language entry**
> (`NaturalLanguageEntryService`) and the **Boundary Advisor** — which scopes
> *what a company must measure* before any number is entered, and sits upstream
> of every feature here. See `BOUNDARY_ADVISOR_PLAN.md`.
>
> **Provider note:** `ClaudeService::message()` accepts an `options['timeout']`
> override. Large prompts (the boundary catalogue, long document extractions)
> exceed the 30-second chat default and will silently fall back or return null
> without it.

---

## What we already have (the foundation)

| Asset | Location | Reuse for new AI features |
|---|---|---|
| Claude API wrapper | `app/Services/AI/ClaudeService.php` | Every feature below calls this — model, temperature, timeout, JSON extraction already configured |
| OCR + AI bill extraction | `app/Services/BillDataExtractor.php` | Template for "noisy text → structured JSON with confidence" |
| Rule-based scope finder | `app/Http/Controllers/ScopeClassifierController.php` | Upgrade target for Feature 1 |
| Analytics aggregations | `app/Services/EmissionAnalyticsService.php` | Data source for the chat assistant (Feature 5) |
| Factor library (now versioned) | `EmissionFactor`, `EioFactor` + gas/GWP fields | Matching target for Feature 2; staleness checks for Feature 8 |
| Supplier survey → Scope 3 | `app/Services/SupplierSurveyEmissionConverter.php` | Extend with AI parsing (Feature 3) |
| Review / Data Quality workflow | `ReviewDataController`, `DataQualityController` | Destination for low-confidence AI output across all features |

**Design principle for every feature:** AI proposes, the audit trail records,
and (depending on the autonomy setting) a human approves. Carbon figures are
audited (ISO 14064-3 / CSRD assurance), so AI output must be *traceable*:
store the model, prompt version, confidence, and the human who accepted it.

---

## Tier 1 — High value, low effort (reuse `ClaudeService` directly)

### 1. AI Scope Classifier
**What:** Replace/augment the rule-based `ScopeClassifierController`. User types a
free-text activity ("bought 200 steel beams from a UAE supplier", "staff flights
LHR→DXB") → Claude returns **Scope (1/2/3)**, **Scope 3 category (1–15)**,
**suggested emission source + factor**, **calculation method**, and a confidence
score.
**Why:** Misclassification is the #1 data-quality problem in GHG accounting.
Directly speeds up data entry and reduces errors.
**Builds on:** `ClaudeService`, `Scope3Category`, `EmissionFactor`.
**Touches:** entry forms (Scope 1/2/3), `EmissionRecordController`.
**Effort:** Low.
**Output:** structured JSON, pre-fills the entry form (human confirms).

### 2. Smart Emission-Factor Matching
**What:** On manual entry or CSV import, map messy free-text source names
("elec.", "diesel gen", "nat gas") to the correct `EmissionFactor` / `EioFactor`
row + unit, preferring country-specific and active dataset versions.
**Why:** Removes the hardest part of entry/import; improves consistency.
**Builds on:** factor versioning fields we just added (`is_active`, `dataset_version`).
**Touches:** `EmissionImportController`, `EmissionRecordController`.
**Effort:** Low–medium.

### 3. AI-Assisted Supplier Survey Parsing
**What:** Supplier replies are often unstructured (PDFs, emails, "our product is
~2.3 kg CO₂ each"). Claude parses them into structured Scope 3 emissions with a
confidence score, feeding the existing converter.
**Why:** Scope 3 supplier data is the biggest, messiest data source.
**Builds on:** `SupplierSurveyEmissionConverter`, supplier portal.
**Touches:** `SupplierSurveyController`, review queue.
**Effort:** Low–medium.

### 4. Anomaly & Data-Quality Detection
**What:** Flag outliers and likely errors: "March electricity is 8× February —
possible unit error (MWh entered as kWh)", duplicate records, missing months.
Claude explains *why* something looks wrong, in plain language.
**Why:** Catches errors before they reach a report or auditor.
**Builds on:** `EmissionAnalyticsService`, `DataQualityController` review workflow.
**Touches:** Review/Data Quality screens (badges + explanations).
**Effort:** Medium (stats + AI explanation layer).

---

## Tier 2 — High value, medium effort

### 5. "Ask Your Emissions Data" Assistant (NL → insight)
**What:** A chat box on Analytics: *"What drove our Scope 3 increase in 2025?"*
Claude uses **tool-use** over `EmissionAnalyticsService` (it requests
aggregations, never raw row dumps) and answers with the numbers it used cited.
**Why:** The most visible, demo-friendly "AI" feature; turns the dashboard into a
self-serve analyst.
**Builds on:** `EmissionAnalyticsService`, `AnalyticsController`.
**Touches:** new chat endpoint + Analytics UI panel.
**Effort:** Medium.
**Safety:** read-only tools, company-scoped, never fabricates figures.

### 6. AI Disclosure Narrative Generator
**What:** We just built CSRD/ESRS E1, CDP and GRI 305 **datapoint** exports
(numbers). This generates the **qualitative narrative** those frameworks also
require: methodology statements, base-year recalculation notes, Scope 2 dual-
reporting rationale, transition-plan summaries — pre-filled from the company's
actual figures.
**Why:** Natural follow-on; the data layer already exists. Big time-saver for
sustainability teams; strong enterprise/sales hook.
**Builds on:** `DisclosureReportService`, the gas/GWP/dual-reporting data.
**Touches:** Disclosure Reports page (add "Generate narrative" → editable draft).
**Effort:** Medium.

### 7. Reduction-Recommendation Engine
**What:** From a company's hotspots, Claude proposes prioritized abatement actions
with rough impact estimates ("switch Plant A to a green tariff → ~340 tCO₂e",
"electrify the diesel fleet → ~X"). Ties into the market-based instruments
(`EnergyAttributeCertificate`) we now track.
**Why:** Moves the product from *accounting* to *decarbonization planning* — where
competitors (Watershed, Sweep, Plan A) win deals.
**Builds on:** hotspots analytics, targets, energy certificates.
**Effort:** Medium.

---

## Tier 3 — Strategic / differentiating

### 8. Automated Factor & Regulation Updates
**What:** Claude summarizes new DEFRA/EPA/IEA factor releases and flags which of
your factors are stale, using the `valid_to` / `dataset_version` fields we added.
**Effort:** Medium. **Value:** keeps the inventory defensible without manual research.

### 9. Target / SBTi Assistant
**What:** Drafts science-based near-term & net-zero targets, checks alignment with
a 1.5 °C trajectory, and tracks progress vs the baseline.
**Effort:** Medium–high. **Value:** fills a known competitive gap.

### 10. Bulk Document Ingestion (beyond bills)
**What:** Extend the OCR/AI pipeline from utility bills to invoices, travel
receipts, freight/logistics docs → auto-create draft emission records.
**Builds on:** `BillDataExtractor` pattern.
**Effort:** Medium–high. **Value:** removes manual data entry at scale.

### 11. Report Summarizer / Executive Brief
**What:** One-paragraph plain-English executive summary auto-attached to any
generated report ("Total emissions fell 6% YoY, driven by…").
**Effort:** Low. **Value:** nice polish on existing PDF/scheduled reports.

---

## Recommended sequencing

The original order — #1, #5, #6, then #4/#2, then #7/#9 — has been followed
except for #6. Everything except #6 in that list is now shipped.

**What to build next, as of 2026-08-05:**

1. **#6 Disclosure Narrative Generator** — the only unbuilt Tier 2 item, and the
   disclosure data layer it needs already exists.
2. **Wire up #3** — `SupplierMatchingService` is written but has no controller,
   so supplier replies are still parsed by hand.
3. **Boundary coverage → Data Health** — surface `BoundaryCoverageService` on the
   Data Health page (phase 5 of `BOUNDARY_ADVISOR_PLAN.md`). Cheapest remaining
   win: it turns the boundary from a one-off wizard into a recurring work queue.
4. **#7 / #9** (recommendations, SBTi) as the decarbonization-planning
   differentiator.

---

## Cross-cutting implementation notes (apply to all features)

**Autonomy model — two options to choose per feature:**
- **Suggest-only (recommended for compliance):** AI fills a *draft*; a person
  approves before it becomes an active record. Safest for audit/assurance.
- **Auto-apply with confidence threshold:** high-confidence (e.g. ≥0.9) applies
  automatically; lower confidence routes to the existing Review queue.

**Auditability (non-negotiable for carbon data):** for every AI-derived value,
store `model`, `prompt_version`, `confidence`, `ai_generated_at`, and
`accepted_by`. Reuse the `data_source` / `confidence_level` columns and the
review workflow already on `emission_records`.

**Cost control:**
- Enable **prompt caching** on the system prompt + factor/category reference data (large, static).
- Batch where possible (e.g. classify a whole CSV in one structured call).
- Use a smaller model (Haiku) for classification/matching, a larger one (Sonnet/Opus) for narratives and the chat assistant.

**Tenant safety:** every AI call must be company-scoped; the chat assistant must
use read-only, aggregated tools (never raw cross-tenant rows). Honor the existing
`HasCompanyScope` boundary.

**Configuration:** add per-company toggles (e.g. `ai_enabled`, `ai_autonomy`,
`ai_confidence_threshold`) via the existing `CompanySetting` model so AI can be
turned on/off per tenant — important for conservative/assurance-bound customers.

**Demo accounts:** gate AI features behind the existing demo-restriction helpers
so demo users see but cannot trigger paid API calls.
