<?php

return [

    /*
    |--------------------------------------------------------------------------
    | EAD "Deliverable C" workbook template
    |--------------------------------------------------------------------------
    |
    | The official Environment Agency – Abu Dhabi MRV monitoring-plan template.
    | EadWorkbookFiller loads it and writes values into its own cells, so the
    | download is a true EAD submission with EAD's formatting, dropdowns and
    | inter-sheet formulas intact.
    |
    | WHY THIS IS A PATH AND NOT A BUNDLED ASSET
    |
    | The template carries EAD's own confidentiality notice: its contents "must
    | not be distributed without prior consent". Committing it to the repository
    | would redistribute it to everyone who clones, and shipping it inside the
    | product would redistribute it to every customer. So the file is installed
    | by the operator, out of band, and named here.
    |
    | It also gives each deployment a way to point at the exact template EAD
    | issued them. The cell map in EadWorkbookFiller is pinned to v8.1 — a
    | different version moves rows, and writing into the wrong cells of a
    | regulatory submission is worse than not producing one.
    |
    | WHY base_path() AND NOT storage_path()
    |
    | FilesystemTenancyBootstrapper suffixes storage_path() per tenant, so
    | inside a tenant request storage_path('app/...') resolves to
    | storage/tenant{id}/app/... — a directory that holds one client's uploads
    | and has never contained this template. That is a bug this default exists
    | to prevent: the workbook is shared reference data, identical for every
    | client, and belongs outside per-tenant storage. base_path() is not
    | rewritten by tenancy.
    |
    | WHY `?:` AND NOT env()'s OWN DEFAULT
    |
    | A key that is present but blank — `MRV_EAD_TEMPLATE_PATH=`, which is
    | exactly what copying .env.example produces — reaches env() as an empty
    | string. An empty string is a value, so env()'s default would never
    | apply and the path would resolve to ''.
    |
    */

    'ead_template' => env('MRV_EAD_TEMPLATE_PATH')
        ?: base_path('storage/app/templates/ead_deliverable_c.xlsx'),

    /*
    |--------------------------------------------------------------------------
    | Controlled vocabularies
    |--------------------------------------------------------------------------
    |
    | Transcribed from sheet "4k - Reference Lists" of the EAD template. These
    | are not our categories to invent or extend: the workbook's own cells are
    | dropdowns bound to these exact strings, so a value typed freehand — even
    | an obviously equivalent one like "Iron & steel production" — is a value
    | EAD's validation rejects.
    |
    | They live in config rather than a table because they change only when EAD
    | issues a new template, which is the same event that invalidates the cell
    | map in EadWorkbookFiller. Keeping both in code means one review covers
    | both, instead of a data migration that can drift from the file it
    | describes.
    |
    | Wording is verbatim, including "[Primary] Aluminium" and the German
    | spellings in the chemicals benchmarks. They look like typos and are not:
    | they are the strings the workbook matches on.
    |
    */

    'primary_activities' => [
        'Combustion of fuels',
        'Production of coke',
        'Metal ore roasting or sintering',
        'Production of iron or steel',
        'Production of aluminium',
        'Production of cement clinker',
        'Production of glass',
        'Other',
    ],

    'product_benchmarks' => [
        'Refinery products', 'Coke', 'Sintered ore', 'Hot metal', 'EAF carbon steel',
        'EAF high alloy steel', 'Iron casting', 'Pre-bake anode', '[Primary] Aluminium',
        'Grey cement clinker', 'White cement clinker', 'Lime', 'Dolime', 'Sintered dolime',
        'Float glass', 'Bottles and jars of colourless', 'Bottles and jars of coloured glass',
        'Continuous filament glass fibre', 'Facing bricks', 'Pavers', 'Roof tiles',
        'Spray dried powder', 'Mineral wool', 'Plaster', 'Dried secondary gypsum', 'Plasterboard',
        'Short fibre kraft pulp', 'Long fibre kraft pulp', 'Sulphite pulp, thermo-mechanical',
        'Recovered paper pulp', 'Newsprint', 'Uncoated fine paper', 'Coated fine paper', 'Tissue',
        'Testliner and fluting', 'Uncoated carton board', 'Coated carton board', 'Carbon black',
        'Nitric acid', 'Adipic acid', 'Ammonia', 'Steam cracking', 'Aromatics', 'Styrene',
        'Phenol/ acetone', 'Ethylenoxid / Ethylenglykol', 'Vinylchlorid-Monomer (VCM)',
        'S-PVC', 'E-PVC', 'Hydrogen', 'Synthesis gas', 'Soda ash',
        'Heat Benchmark', 'Fuel Benchmark', 'Process Emissions', 'Other',
    ],

    /*
     * Stored key => the exact label the workbook expects. EadWorkbookFiller
     * translates on the way out, so the database keeps a stable identifier
     * while the cell gets EAD's wording.
     */
    'methodologies' => [
        'calculation' => 'Calculation-based',
        'measurement' => 'Measurement-based',
        'fallback' => 'Fall-back',
    ],

    'stream_types' => [
        'fuel_combusted' => 'Fuel combusted',
        'other_input' => 'Other input',
        'output' => 'Output',
    ],

    /*
     * Not from 4k — the greenhouse gases a facility can report against a
     * source. The template asks for free text in 2c2 column F, but an
     * open field there produces "CO2", "Co2", "carbon dioxide" and
     * "CO₂" across four facilities of the same operator.
     */
    'ghg_types' => [
        'CO2' => 'CO₂ only',
        'CH4' => 'CH₄ only',
        'N2O' => 'N₂O only',
        'CO2, CH4' => 'CO₂ and CH₄',
        'CO2, CH4, N2O' => 'CO₂, CH₄ and N₂O',
        'Mixed' => 'Mixed / other',
    ],

    /*
    |--------------------------------------------------------------------------
    | Mitigation measures — sheet 4J
    |--------------------------------------------------------------------------
    |
    | The one part of the workbook that spans all three scopes: 4J asks for
    | every action the facility is taking, planning or studying, whether the
    | reduction lands in Scope 1, 2 or 3. Its instruction is explicit — one row
    | per measure, never combine actions — so this is a repeating table, not a
    | narrative.
    |
    */

    'mitigation_categories' => [
        'Emission Reduction',
        'Emission Avoidance',
        'Carbon Removal',
        'External Offset',
    ],

    'mitigation_statuses' => [
        'Implemented',
        'Planned',
        'Feasibility Study',
        'Discontinued',
    ],

    'mitigation_verification' => [
        'Not verified',
        'Internally verified',
        'Third-party verified',
    ],

];
