# Emission factor source files

The publishers' own files, committed unmodified. Every factor row in
`emission_factors` that carries a `dataset_name` was produced by parsing one of
these, and records its `source_file_hash` so a row can be traced back to the
exact bytes it came from.

**They are committed rather than downloaded at build time on purpose.** A URL can
change, a publisher can revise a file in place, and a factor's provenance has to
outlive both. The point of this work is that "where did this number come from?"
has an answer years later; a link that 404s is not one.

Verify at any time:

```bash
php artisan factors:verify-sources
```

---

## DEFRA / DESNZ — UK Government GHG Conversion Factors 2026

| | |
|---|---|
| **File** | `defra-2026-flat.xlsx` |
| **Publisher** | Department for Energy Security and Net Zero (DESNZ) |
| **Edition** | 2026 (flat file, revised July 2026) |
| **Retrieved** | 2026-09-01 |
| **Source** | https://www.gov.uk/government/publications/greenhouse-gas-reporting-conversion-factors-2026 |
| **Direct URL** | https://assets.publishing.service.gov.uk/media/6a6c9748862aaf18d9c62ac9/ghg-conversion-factors-2026-flat-format-revised.xlsx |
| **Size** | 515,426 bytes |
| **SHA-256** | `a9a455ab396dae226d510c7be6233748416d490c41a5d20f3dc7a0c45feecd5e` |
| **Licence** | Open Government Licence v3.0 |
| **Attribution** | Contains public sector information licensed under the Open Government Licence v3.0. |

Published by DESNZ explicitly "for automatic processing only" — a flat table of
8,748 rows, which is why it is the cleanest of the two to import.

Shape: `ID | Scope | Level 1..4 | Column Text | UOM | GHG/Unit | GHG Conversion Factor`.

Each factor appears up to four times under `GHG/Unit`:

| `GHG/Unit` | Meaning |
|---|---|
| `kg CO2e` | the total — becomes `factor_value` |
| `kg CO2e of CO2 per unit` | becomes `co2_factor` |
| `kg CO2e of CH4 per unit` | becomes `ch4_factor` |
| `kg CO2e of N2O per unit` | becomes `n2o_factor` |

**These per-gas figures are already CO2e-weighted** — "kg CO₂e *of* CH₄", not raw
kg CH₄. They must not be multiplied by a GWP again. This differs from
`config/scope1_sources.php`, whose `ch4`/`n2o` are raw kg per TJ and *do* need
weighting. Conflating the two overstates the gas split by the GWP factor.

## US EPA — GHG Emission Factors Hub 2025

| | |
|---|---|
| **File** | `epa-2025-hub.xlsx` |
| **Publisher** | US EPA, Center for Corporate Climate Leadership |
| **Edition** | 2025 (last modified January 15, 2025) |
| **Retrieved** | 2026-09-01 |
| **Source** | https://epa.gov/climateleadership/ghg-emission-factors-hub |
| **Direct URL** | https://www.epa.gov/system/files/other-files/2025-01/ghg-emission-factors-hub-2025.xlsx |
| **Size** | 1,014,275 bytes |
| **SHA-256** | `43afb91d79b2ae765b3a447549d9e1021a144a407cec5eb804be9fcf69a668a7` |
| **Licence** | US federal government work — public domain |

### Why 2025 and not 2026

**EPA has not published a 2026 edition.** Verified against EPA's own download page
on 2026-09-01: the page was last updated 2026-01-12 and the newest edition
offered is still 2025, with archives back to 2011.

Search results referring to a "2026 GHG Emission Factors Hub" point to a product
from a third-party data vendor, not an EPA publication. Importing that and
attributing it to EPA would put a citation on client figures that EPA never
issued — the precise failure this directory exists to prevent. If EPA publishes a
2026 edition, add it here alongside this one; the importer supersedes rather than
overwrites, so both can coexist.

### Shape

Unlike the DEFRA flat file this is a presentation workbook: one sheet, 591 rows,
twelve tables stacked vertically, each with its own columns, section sub-headings
that carry no data, and footnotes. Each table needs its own mapping.

| Table | Contents |
|---|---|
| 1 | Stationary Combustion |
| 2 | Mobile Combustion CO2 |
| 3 | Mobile Combustion CH4/N2O — on-road gasoline |
| 4 | Mobile Combustion CH4/N2O — on-road diesel and alternative fuel |
| 5 | Mobile Combustion CH4/N2O — non-road |
| 6 | Electricity (eGRID subregions) |
| 7 | Steam and Heat |
| 8 | Scope 3 Cat 4 & 9 — transportation and distribution |
| 9 | Scope 3 Cat 5 & 12 — waste |
| 10 | Scope 3 Cat 6 & 7 — business travel and commuting |
| 11 | Global Warming Potentials |
| 12 | GWPs for blended refrigerants |

Tables 1–5 give heat content **and** per-gas factors in two unit bases (per mmBtu
and per short ton / gallon), which populates `net_calorific_value` and
`ef_per_energy` as well as the gas columns.

EPA's stated GWPs are CH₄ = 28, N₂O = 265 — AR5, matching `App\Support\Gwp`'s
current basis. If this application re-bases to AR6, EPA 2025 factors stay on AR5
and their `gwp_version` must continue to say so rather than following the app.

---

## Not included

**IPCC** has no bulk export. The Emission Factor Database (EFDB) is a web
interface; bulk data is requested from the Technical Support Unit at
`ipcc-efdb@iges.or.jp`. The IPCC 2006 Guidelines default factors this application
already uses came from `config/scope1_sources.php`, whose per-source `note` fields
carry the citations (e.g. "IPCC 98300 kgCO2/TJ, NCV 26.5 GJ/t").

## Adding a source

1. Download the publisher's own file — never a reseller's copy, never transcribed values.
2. Commit it here unmodified.
3. Add a section above with URL, retrieval date, byte size, SHA-256 and licence.
4. Write an importer under `app/Services/Factors/Import/`.
5. Record the attribution requirement if the licence has one.
