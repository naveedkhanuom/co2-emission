<?php

/**
 * Scope 1 Direct Emissions - sub-categories and sources with emission factors.
 * Used by the Scope 1 Entry page (stationary, mobile, fugitive).
 * Units: co2 = kgCO2 per unit, ch4/n2o = kg per TJ, ncv = TJ per unit (for combustion).
 * Fugitive: isFug=true, gwp = GWP (AR5), optional gwpM3 for methane m3.
 */
return [
    'GWP_CH4' => 28,
    'GWP_N2O' => 265,
    'stationary' => [
        ['name' => 'Biomass Combustion', 'desc' => 'Biomass for heat/power', 'units' => [
            ['u' => 'kg', 'label' => 'Kilograms (kg)', 'co2' => 0, 'ch4' => 30, 'n2o' => 4, 'ncv' => 0.0000156],
            ['u' => 'tonnes', 'label' => 'Tonnes (t)', 'co2' => 0, 'ch4' => 30, 'n2o' => 4, 'ncv' => 0.0156],
        ], 'note' => 'Biogenic CO2 not counted. IPCC Table 2.4/2.5'],
        ['name' => 'Coal Combustion', 'desc' => 'Coal in boilers', 'units' => [
            ['u' => 'kg', 'label' => 'Kilograms (kg)', 'co2' => 2.42, 'ch4' => 1, 'n2o' => 1.5, 'ncv' => 0.0000261],
            ['u' => 'tonnes', 'label' => 'Tonnes (t)', 'co2' => 2420, 'ch4' => 1, 'n2o' => 1.5, 'ncv' => 0.0261],
        ], 'note' => 'IPCC 98300 kgCO2/TJ'],
        ['name' => 'Diesel (Stationary)', 'desc' => 'Generators, boilers', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.676, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.0000359],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 10.13, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.000136],
        ], 'note' => 'IPCC 74100 kgCO2/TJ'],
        ['name' => 'Fuel Oil (Heating Oil)', 'desc' => 'Residual fuel', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.96, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.0000388],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 11.21, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.000147],
        ], 'note' => 'IPCC 77400 kgCO2/TJ'],
        ['name' => 'Gasoline (Stationary)', 'desc' => 'Stationary engines', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.315, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.0000323],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 8.764, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.000122],
        ], 'note' => 'IPCC 69300 kgCO2/TJ'],
        ['name' => 'Kerosene', 'desc' => 'Heaters, generators', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.538, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.0000349],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 9.607, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.000132],
        ], 'note' => 'IPCC 71900 kgCO2/TJ'],
        ['name' => 'LPG / Propane', 'desc' => 'LPG combustion', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 1.555, 'ch4' => 1, 'n2o' => 0.1, 'ncv' => 0.0000261],
            ['u' => 'kg', 'label' => 'Kilograms (kg)', 'co2' => 2.983, 'ch4' => 1, 'n2o' => 0.1, 'ncv' => 0.0000473],
        ], 'note' => 'IPCC 63100 kgCO2/TJ'],
        ['name' => 'Natural Gas', 'desc' => 'Boilers, furnaces, CHP', 'units' => [
            ['u' => 'm3', 'label' => 'Cubic Meters (m3)', 'co2' => 2.024, 'ch4' => 1, 'n2o' => 0.1, 'ncv' => 0.0000364],
            ['u' => 'kWh', 'label' => 'kWh', 'co2' => 0.202, 'ch4' => 1, 'n2o' => 0.1, 'ncv' => 0.0000036],
            ['u' => 'therms', 'label' => 'Therms', 'co2' => 5.31, 'ch4' => 1, 'n2o' => 0.1, 'ncv' => 0.0001055],
        ], 'note' => 'IPCC 56100 kgCO2/TJ'],
        ['name' => 'Wood / Pellets', 'desc' => 'Wood heating', 'units' => [
            ['u' => 'kg', 'label' => 'Kilograms (kg)', 'co2' => 0, 'ch4' => 30, 'n2o' => 4, 'ncv' => 0.0000156],
            ['u' => 'tonnes', 'label' => 'Tonnes (t)', 'co2' => 0, 'ch4' => 30, 'n2o' => 4, 'ncv' => 0.0156],
        ], 'note' => 'Biogenic CO2 not counted'],
    ],
    'mobile' => [
        ['name' => 'Aviation - Jet Fuel', 'desc' => 'Company aircraft', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.534, 'ch4' => 0.5, 'n2o' => 2, 'ncv' => 0.0000349],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 9.593, 'ch4' => 0.5, 'n2o' => 2, 'ncv' => 0.000132],
        ], 'note' => 'IPCC 71500 kgCO2/TJ'],
        ['name' => 'Fleet - Biodiesel', 'desc' => 'Biodiesel vehicles', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 0, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.0000337],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 0, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.000128],
        ], 'note' => 'Biogenic CO2 not counted'],
        ['name' => 'Fleet - CNG', 'desc' => 'CNG fleet', 'units' => [
            ['u' => 'kg', 'label' => 'Kilograms (kg)', 'co2' => 2.743, 'ch4' => 92, 'n2o' => 3, 'ncv' => 0.0000480],
        ], 'note' => 'IPCC 56100 kgCO2/TJ mobile'],
        ['name' => 'Fleet - Diesel', 'desc' => 'Diesel cars/vans', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.676, 'ch4' => 3.9, 'n2o' => 3.9, 'ncv' => 0.0000359],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 10.13, 'ch4' => 3.9, 'n2o' => 3.9, 'ncv' => 0.000136],
        ], 'note' => 'IPCC 74100 kgCO2/TJ mobile'],
        ['name' => 'Fleet - Gasoline', 'desc' => 'Gasoline cars/vans', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.315, 'ch4' => 3.5, 'n2o' => 5.7, 'ncv' => 0.0000323],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 8.764, 'ch4' => 3.5, 'n2o' => 5.7, 'ncv' => 0.000122],
        ], 'note' => 'IPCC 69300 kgCO2/TJ mobile'],
        ['name' => 'Fleet - LPG', 'desc' => 'LPG vehicles', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 1.555, 'ch4' => 62, 'n2o' => 0.2, 'ncv' => 0.0000261],
        ], 'note' => 'IPCC 63100 kgCO2/TJ mobile'],
        ['name' => 'Marine - Diesel', 'desc' => 'Vessel diesel', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.676, 'ch4' => 7, 'n2o' => 2, 'ncv' => 0.0000359],
            ['u' => 'tonnes', 'label' => 'Tonnes (t)', 'co2' => 3188, 'ch4' => 7, 'n2o' => 2, 'ncv' => 0.0431],
        ], 'note' => 'IPCC 74100 kgCO2/TJ marine'],
        ['name' => 'Marine - HFO', 'desc' => 'HFO vessels', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 3.114, 'ch4' => 7, 'n2o' => 2, 'ncv' => 0.0000404],
            ['u' => 'tonnes', 'label' => 'Tonnes (t)', 'co2' => 3206, 'ch4' => 7, 'n2o' => 2, 'ncv' => 0.0404],
        ], 'note' => 'IPCC 77400 kgCO2/TJ marine'],
        ['name' => 'Off-road - Diesel', 'desc' => 'Construction/mining', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.676, 'ch4' => 4.15, 'n2o' => 28.6, 'ncv' => 0.0000359],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 10.13, 'ch4' => 4.15, 'n2o' => 28.6, 'ncv' => 0.000136],
        ], 'note' => 'IPCC off-road Table 3.3.1'],
        ['name' => 'Rail - Diesel', 'desc' => 'Company locomotives', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.676, 'ch4' => 4.15, 'n2o' => 28.6, 'ncv' => 0.0000359],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 10.13, 'ch4' => 4.15, 'n2o' => 28.6, 'ncv' => 0.000136],
        ], 'note' => 'IPCC rail diesel'],
    ],
    'fugitive' => [
        ['name' => 'Fire Suppression (Halon)', 'desc' => 'Halon systems', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 0, 'note' => 'Halon ODP substance'],
        ['name' => 'Fire Suppression (HFCs)', 'desc' => 'HFC fire systems', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 3220, 'note' => 'HFC-227ea GWP=3220 AR5'],
        ['name' => 'Methane Leakage', 'desc' => 'Gas pipe leaks', 'units' => [
            ['u' => 'kg', 'label' => 'Kilograms (kg)'],
            ['u' => 'm3', 'label' => 'Cubic Meters (m3)'],
        ], 'isFug' => true, 'gwp' => 28, 'gwpM3' => 18.76, 'note' => 'CH4 GWP=28 AR5'],
        ['name' => 'N2O from Processes', 'desc' => 'Industrial N2O', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 265, 'note' => 'N2O GWP=265 AR5'],
        ['name' => 'PFCs (Aluminium)', 'desc' => 'PFC production', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 7390, 'note' => 'CF4 GWP=7390 AR5'],
        ['name' => 'Refrigerant (CFCs)', 'desc' => 'CFC HVAC leaks', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 10900, 'note' => 'CFC-12 GWP=10900'],
        ['name' => 'Refrigerant (HCFCs)', 'desc' => 'HCFC e.g. R-22', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 1810, 'note' => 'HCFC-22 GWP=1810'],
        ['name' => 'Refrigerant (HFCs)', 'desc' => 'HFC e.g. R-410A', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 2088, 'note' => 'R-410A GWP=2088'],
        ['name' => 'Refrigerant (HFOs)', 'desc' => 'HFO/natural refrig', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 4, 'note' => 'HFO GWP<4'],
        ['name' => 'SF6 (Electrical)', 'desc' => 'SF6 switchgear', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 23500, 'note' => 'SF6 GWP=23500 AR5'],
    ],
];
