<?php

/**
 * Global Warming Potential (GWP) reference sets — 100-year horizon.
 *
 * Used to convert masses of individual greenhouse gases into CO2-equivalent and
 * to label which assessment report a figure was computed under. Disclosure
 * frameworks (CSRD/ESRS E1, CDP, GHG Protocol) require stating the GWP set used;
 * AR6 is the current default for new inventories, AR5 remains common, AR4 is
 * legacy. Values are IPCC AR4/AR5/AR6 GWP-100 (fossil CH4 where applicable).
 */
return [
    // Default set applied to new records when neither the factor nor the company
    // specifies one.
    'default' => 'ar6',

    // The GWP basis the bundled emission-factor / source tables
    // (config/scope1_sources.php, scope2_sources.php, seeded factors) were
    // actually built on. Every record is stamped with THIS so its stated GWP set
    // matches the math that produced its co2e_value. Bump to 'ar6' only after the
    // factor tables (incl. refrigerant GWPs) are re-based to AR6.
    'factor_basis' => 'ar5',

    'labels' => [
        'ar4' => 'IPCC AR4 (2007)',
        'ar5' => 'IPCC AR5 (2013)',
        'ar6' => 'IPCC AR6 (2021)',
    ],

    // GWP-100 by gas, per assessment report.
    'sets' => [
        'ar4' => [
            'co2'           => 1,
            'ch4'           => 25,
            'ch4_biogenic'  => 25,
            'n2o'           => 298,
            'hfc134a'       => 1430,
            'hfc125'        => 3500,
            'hfc143a'       => 4470,
            'hfc32'         => 675,
            'sf6'           => 22800,
            'nf3'           => 17200,
            'r404a'         => 3922,
            'r410a'         => 2088,
        ],
        'ar5' => [
            'co2'           => 1,
            'ch4'           => 28,
            'ch4_biogenic'  => 28,
            'n2o'           => 265,
            'hfc134a'       => 1300,
            'hfc125'        => 3170,
            'hfc143a'       => 4800,
            'hfc32'         => 677,
            'sf6'           => 23500,
            'nf3'           => 16100,
            'r404a'         => 3943,
            'r410a'         => 1924,
        ],
        'ar6' => [
            'co2'           => 1,
            'ch4'           => 27.9,   // fossil methane
            'ch4_biogenic'  => 27.0,
            'n2o'           => 273,
            'hfc134a'       => 1526,
            'hfc125'        => 3740,
            'hfc143a'       => 5810,
            'hfc32'         => 771,
            'sf6'           => 25200,
            'nf3'           => 17400,
            'r404a'         => 4728,
            'r410a'         => 2256,
        ],
    ],
];
