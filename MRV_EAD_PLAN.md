# MRV / Regulatory Layer (EAD UAE + EU-ETS) — Implementation Plan

> **Goal:** Keep the global GHG-Protocol engine (`CO₂e = Activity × Factor`) as the
> universal default, and add an **optional, opt-in MRV layer** on top of it that can
> produce regulator-grade facility submissions — starting with the **Environment
> Agency – Abu Dhabi (EAD)** template, which is modelled on EU-ETS / EU-MRV.
>
> **Principle: everything here is additive.** No existing entry flow, calculation,
> or report changes. A facility that does not enable "MRV mode" behaves exactly as
> today. The MRV layer only activates per-facility, per-reporting-year, on request.

---

## 1. Why a layer, not a rewrite

| | Core (today) | MRV layer (new, optional) |
|---|---|---|
| Formula | `CO₂e = Activity × Factor` | `Emissions = Activity × NCV × EF × Oxidation × Conversion` |
| Factor | One combined `tCO₂e/unit` | Decomposed: NCV, EF, Oxidation, Conversion |
| Scopes | 1 / 2 / 3 | Scope 1 only |
| Methods | Activity-based, spend-based | Calculation-based, **measurement-based (CEMS)**, fall-back |
| Rigour | `confidence_level` | EU-ETS **Tiers 1–4 + uncertainty %**, materiality (major/minor/de-minimis) |
| Audience | Every company, globally | Regulated heavy-industry installations (UAE, EU) |

The EU-ETS formula is a **decomposition** of the combined factor, not a different
answer:

```
factor_value (tCO₂e/unit)  ≡  NCV × EF × Oxidation × Conversion   (after unit normalisation)
```

So the two reconcile. We store the components when a facility needs them, and fall
back to the single combined factor everywhere else.

---

## 2. Scope of phase 1

**In scope:** Produce a complete, valid EAD MRV workbook for **one facility, one
calendar year**, using the **calculation-based** approach (the most common case),
plus all the descriptive/governance sheets.

**Deferred to phase 2:** measurement-based (CEMS) numeric path, fall-back uncertainty
proof automation, EU-ETS-specific export variant.

---

## 3. Data model changes (all additive — new columns are nullable, new tables are opt-in)

### 3.1 `emission_factors` — add optional decomposed components
These are **nullable**. When null (the normal case) nothing changes; the combined
`factor_value` is authoritative as today.

```
ALTER emission_factors ADD:
  net_calorific_value     decimal(16,8) null   -- e.g. 42.3
  ncv_unit                string null          -- e.g. "TJ/Gg"
  ef_per_energy           decimal(16,8) null   -- e.g. 73.3  (tCO₂/TJ)
  ef_per_energy_unit      string null
  oxidation_factor        decimal(8,6) null    -- default 1
  conversion_factor       decimal(8,6) null    -- default 1
  ipcc_reference          string null          -- provenance for the components
```
*Validation:* when all components are present, assert
`factor_value ≈ normalise(NCV × EF × Oxidation × Conversion)` within tolerance, so
the decomposed and combined values can never silently diverge.

### 3.2 `facilities` — promote to a first-class regulated entity
Today `emission_records.facility` is just a **string**. EAD is facility-anchored, so
we add the regulatory identifiers to the `facilities` table and (phase 1) match by
name, (phase 2) migrate the string to a real FK.

```
ALTER facilities ADD:
  mrv_enabled              boolean default false   -- the per-facility opt-in toggle
  economic_licence_number  string null
  environmental_permit_no  string null
  parent_entity            string null
  coordinates              string null             -- "lat,lng" of main entrance
  primary_sector           string null             -- from EAD reference list
  primary_activity         string null             -- e.g. "Combustion of fuels"
```

### 3.3 New table: `mrv_source_streams`
The concept your schema is missing. A source stream = a fuel/material input or output
monitored under the calculation approach.

```
mrv_source_streams
  id, company_id, facility_id, reporting_year
  stream_code            string        -- "F01"
  description            string
  emission_source_code   string        -- links to "S01" below
  classification         enum(fuel_combusted, other_input, output)
  fuel_type              string null
  activity_level         decimal       -- e.g. 10000
  activity_unit          string        -- "MWh"
  combustion_device      string null
  device_capacity        decimal null
  device_capacity_unit   string null
  -- tiers & uncertainty
  materiality            enum(major, minor, de_minimis)
  tier_level             tinyint null  -- 1..4
  uncertainty_pct        decimal null
  accuracy_source        string null   -- "Lab. Analysis", etc.
  -- decomposed calc inputs (mirror of factor components, snapshotted)
  ncv, ef, oxidation_factor, conversion_factor  decimal null
  emission_factor_id     fk null       -- which library factor was used
  estimated_co2e         decimal       -- computed result
```

### 3.4 New table: `mrv_emission_sources`
A physical emission source ("S01"), independent of method.

```
mrv_emission_sources
  id, company_id, facility_id, reporting_year
  source_code            string        -- "S01"
  name, description
  associated_product     string null   -- "P01"
  ghg_types              string        -- "CO2", "CH4", "Mixed"
  energy_related         boolean
  process_emissions      boolean
  methodology            enum(calculation, measurement, fallback)
  total_co2e             decimal
```

### 3.5 New table: `mrv_measuring_instruments` (phase 1 descriptive, phase 2 numeric)
```
mrv_measuring_instruments
  id, company_id, facility_id, reporting_year
  instrument_code, source_stream_code, type, location_id
  range_unit, range_lower, range_upper
  specified_uncertainty_pct, use_range_lower, use_range_upper
```

### 3.6 New table: `mrv_facility_reports` (the submission header + governance text)
One row per facility per year — holds the narrative/governance sheets so a draft can
be saved and re-edited.

```
mrv_facility_reports
  id, company_id, facility_id, reporting_year (unique together)
  status                 enum(draft, finalised, submitted)
  estimated_annual_co2e  decimal
  estimation_justification text
  -- contacts (primary + alternate) as json
  contacts               json
  -- products / benchmarks
  products               json   -- [{id, category, technology, capacity, actual, ...}]
  -- methane (sheet 3g)
  methane_present        boolean
  methane               json    -- volume, co2e, sources, LDAR programme
  -- verification & data gaps (sheet 4h)
  verification_text      text
  data_gaps             json
  -- management & QA (sheet 4i)
  management            json    -- roles, QA procedures, data-validation procedures
  -- mitigation (sheet 4j)
  mitigation_measures   json    -- [{description, category, scope, ghg, start_year, status, reductions, methodology, verification}]
```

> Storing the descriptive sheets as `json` (not 30 new columns) keeps phase 1 light
> and matches how `company_settings` already stores flexible config.

---

## 4. Calculation reconciliation

A single service decides which formula to use, transparently:

```php
// app/Services/MRV/MrvCalculator.php
function co2eForStream(SourceStream $s): float {
    if ($s->hasDecomposedInputs()) {
        // EU-ETS / EAD path
        return normaliseToTonnes(
            $s->activity_level * $s->ncv * $s->ef * $s->oxidation_factor * $s->conversion_factor,
            $s->activity_unit, $s->ncv_unit, $s->ef_unit
        );
    }
    // Fallback to the global combined-factor path (identical to today)
    return $s->activity_level * $s->combinedFactor();
}
```

**The hard part is unit normalisation** (e.g. `MWh × TJ/Gg × tCO₂/TJ`). This is the
one piece needing careful work and a dedicated unit-conversion table + tests. Your
existing `UnitConverter` service is the place to extend.

---

## 5. Export — fill the actual EAD workbook

Reuse your existing Maatwebsite/Excel + DomPDF pattern (`DisclosureReportController`
/ `DisclosureExport`). The cleanest approach for a 14-sheet government template:

- **Template-fill, not generate-from-scratch.** Load the official
  `Deliverable C Template` as a base with `PhpSpreadsheet`, write values into the
  exact cells (`2c1!`, `2c2!C75`, `3d2!`…), and stream it back. This preserves EAD's
  formatting, dropdowns, and inter-sheet formulas.
- New `app/Exports/EadMrvExport.php` + `MrvReportController@export`.
- New route under the existing reports permission set
  (`permission:list-reports|create-report|...`).

```
app/Services/MRV/MrvCalculator.php
app/Services/MRV/EadWorkbookFiller.php      // maps DB -> template cells
app/Exports/EadMrvExport.php
app/Http/Controllers/MrvReportController.php
resources/views/reports/mrv/index.blade.php // facility + year picker, MRV data entry
```

---

## 6. UI / UX

1. **Facility settings:** an "Enable regulatory MRV mode" toggle + the new permit
   fields. Hidden from normal users; only shows when toggled.
2. **MRV workspace** (`/reports/mrv`): pick facility + year → tabbed form mirroring
   the EAD sheets (Identifiers, Sources & Streams, Calculation, Methane, Verification,
   QA, Mitigation). Pre-populates source streams/sources from existing Scope 1
   `emission_records` for that facility/year so users don't re-enter data.
3. **Export buttons:** "Download EAD workbook (.xlsx)" and PDF.

---

## 7. Phasing

| Phase | Deliverable | Effort (rough) |
|---|---|---|
| **1a** | Migrations (factor components, facility fields, MRV tables) + models | S |
| **1b** | `MrvCalculator` + unit normalisation + tests (reconcile combined vs decomposed) | M |
| **1c** | MRV workspace UI + pre-population from existing records | M |
| **1d** | `EadWorkbookFiller` + `.xlsx`/PDF export filling the real template | M |
| **2** | Measurement-based (CEMS) numeric path, fall-back uncertainty automation, EU-ETS export variant | L |

---

## 8. What does NOT change (guarantees)

- The global `Activity × Factor` calculation is untouched.
- Scope 2/3, supplier surveys, analytics, CSRD/CDP/GRI disclosures — all unaffected.
- New factor columns are nullable; existing factors keep working.
- MRV is off by default for every facility and every company.
- No normal user ever sees NCV / oxidation / tiers unless their facility opts in.

---

## 9. Competitive note

No major global competitor (Watershed, Greenly, Plan A, Normative) ships a **native
EAD/UAE MRV export**. This layer lets one engine serve both the global GHG-Protocol
market and Gulf/EU regulated emitters — a genuine differentiator, not just compliance.
