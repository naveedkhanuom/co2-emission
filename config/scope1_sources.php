<?php

/**
 * Scope 1 Direct Emissions - sub-categories and sources with emission factors.
 * Used by the Scope 1 Entry page (stationary, mobile, fugitive).
 *
 * Combustion: co2 = kgCO2 per unit, ch4/n2o = kg per TJ, ncv = TJ per unit.
 * Distance-based mobile (km): co2 = kgCO2e per km (composite), ch4=0, n2o=0, ncv=0.
 * Fugitive: isFug=true, gwp = GWP (AR5), optional gwpM3 for methane m3.
 *
 * Sources: IPCC 2006 Guidelines, DEFRA 2025 (km factors).
 */
return [
    'GWP_CH4' => 28,
    'GWP_N2O' => 265,
    'stationary' => [
        ['name' => 'Agricultural Byproducts', 'desc' => 'Agricultural residues for heat', 'units' => [
            ['u' => 'kg', 'label' => 'Kilograms (kg)', 'co2' => 0, 'ch4' => 30, 'n2o' => 4, 'ncv' => 0.0000116],
            ['u' => 'tonnes', 'label' => 'Tonnes (t)', 'co2' => 0, 'ch4' => 30, 'n2o' => 4, 'ncv' => 0.0116],
        ], 'note' => 'Biogenic CO2 not counted. IPCC Table 2.4/2.5'],
        ['name' => 'Anthracite Coal', 'desc' => 'Anthracite in boilers/furnaces', 'units' => [
            ['u' => 'kg', 'label' => 'Kilograms (kg)', 'co2' => 2.602, 'ch4' => 1, 'n2o' => 1.5, 'ncv' => 0.0000265],
            ['u' => 'tonnes', 'label' => 'Tonnes (t)', 'co2' => 2602, 'ch4' => 1, 'n2o' => 1.5, 'ncv' => 0.0265],
        ], 'note' => 'IPCC 98300 kgCO2/TJ, NCV 26.5 GJ/t'],
        ['name' => 'Biodiesel (B100)', 'desc' => 'Pure biodiesel stationary', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 0, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.0000337],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 0, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.000128],
        ], 'note' => 'Biogenic CO2 not counted'],
        ['name' => 'Bioethanol (E100)', 'desc' => 'Pure bioethanol stationary', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 0, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.0000213],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 0, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.0000807],
        ], 'note' => 'Biogenic CO2 not counted'],
        ['name' => 'Biogas', 'desc' => 'Landfill gas, anaerobic digestion', 'units' => [
            ['u' => 'm3', 'label' => 'Cubic Meters (m3)', 'co2' => 0, 'ch4' => 1, 'n2o' => 0.1, 'ncv' => 0.0000202],
            ['u' => 'kWh', 'label' => 'kWh', 'co2' => 0, 'ch4' => 1, 'n2o' => 0.1, 'ncv' => 0.0000036],
        ], 'note' => 'Biogenic CO2 not counted'],
        ['name' => 'Biomass Combustion', 'desc' => 'Biomass for heat/power', 'units' => [
            ['u' => 'kg', 'label' => 'Kilograms (kg)', 'co2' => 0, 'ch4' => 30, 'n2o' => 4, 'ncv' => 0.0000156],
            ['u' => 'tonnes', 'label' => 'Tonnes (t)', 'co2' => 0, 'ch4' => 30, 'n2o' => 4, 'ncv' => 0.0156],
        ], 'note' => 'Biogenic CO2 not counted. IPCC Table 2.4/2.5'],
        ['name' => 'Bituminous Coal', 'desc' => 'Bituminous coal in boilers', 'units' => [
            ['u' => 'kg', 'label' => 'Kilograms (kg)', 'co2' => 2.325, 'ch4' => 1, 'n2o' => 1.5, 'ncv' => 0.0000246],
            ['u' => 'tonnes', 'label' => 'Tonnes (t)', 'co2' => 2325, 'ch4' => 1, 'n2o' => 1.5, 'ncv' => 0.0246],
        ], 'note' => 'IPCC 94600 kgCO2/TJ, NCV 24.6 GJ/t'],
        ['name' => 'Blast Furnace Gas', 'desc' => 'Blast furnace gas combustion', 'units' => [
            ['u' => 'm3', 'label' => 'Cubic Meters (m3)', 'co2' => 0.642, 'ch4' => 1, 'n2o' => 0.1, 'ncv' => 0.00000247],
            ['u' => 'kWh', 'label' => 'kWh', 'co2' => 0.936, 'ch4' => 1, 'n2o' => 0.1, 'ncv' => 0.0000036],
        ], 'note' => 'IPCC 260000 kgCO2/TJ, low NCV CO2-rich gas'],
        ['name' => 'Butane', 'desc' => 'Butane combustion', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 1.762, 'ch4' => 1, 'n2o' => 0.1, 'ncv' => 0.0000279],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 6.670, 'ch4' => 1, 'n2o' => 0.1, 'ncv' => 0.000106],
        ], 'note' => 'IPCC 63100 kgCO2/TJ'],
        ['name' => 'Coal Combustion', 'desc' => 'Coal in boilers', 'units' => [
            ['u' => 'kg', 'label' => 'Kilograms (kg)', 'co2' => 2.42, 'ch4' => 1, 'n2o' => 1.5, 'ncv' => 0.0000261],
            ['u' => 'tonnes', 'label' => 'Tonnes (t)', 'co2' => 2420, 'ch4' => 1, 'n2o' => 1.5, 'ncv' => 0.0261],
        ], 'note' => 'IPCC 98300 kgCO2/TJ'],
        ['name' => 'Coke Oven Gas', 'desc' => 'Coke oven gas combustion', 'units' => [
            ['u' => 'm3', 'label' => 'Cubic Meters (m3)', 'co2' => 1.718, 'ch4' => 1, 'n2o' => 0.1, 'ncv' => 0.0000387],
            ['u' => 'kWh', 'label' => 'kWh', 'co2' => 0.160, 'ch4' => 1, 'n2o' => 0.1, 'ncv' => 0.0000036],
        ], 'note' => 'IPCC 44400 kgCO2/TJ'],
        ['name' => 'Crude Oil', 'desc' => 'Direct crude oil combustion', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 3.050, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.0000416],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 11.54, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.000157],
        ], 'note' => 'IPCC 73300 kgCO2/TJ'],
        ['name' => 'Diesel (Stationary)', 'desc' => 'Generators, boilers', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.676, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.0000359],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 10.13, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.000136],
        ], 'note' => 'IPCC 74100 kgCO2/TJ'],
        ['name' => 'Ethane', 'desc' => 'Ethane combustion', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 1.075, 'ch4' => 1, 'n2o' => 0.1, 'ncv' => 0.0000175],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 4.070, 'ch4' => 1, 'n2o' => 0.1, 'ncv' => 0.0000661],
        ], 'note' => 'IPCC 61600 kgCO2/TJ'],
        ['name' => 'Fuel Oil (Heating Oil)', 'desc' => 'Residual fuel', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.96, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.0000388],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 11.21, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.000147],
        ], 'note' => 'IPCC 77400 kgCO2/TJ'],
        ['name' => 'Fuel Oil No. 2', 'desc' => 'Distillate fuel oil No. 2', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.676, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.0000359],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 10.21, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.000136],
        ], 'note' => 'IPCC 74100 kgCO2/TJ (similar to diesel)'],
        ['name' => 'Fuel Oil No. 4', 'desc' => 'Distillate fuel oil No. 4', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.895, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.0000374],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 10.96, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.000142],
        ], 'note' => 'IPCC 77400 kgCO2/TJ, NCV 37.4 GJ/kL'],
        ['name' => 'Fuel Oil No. 6 (Residual)', 'desc' => 'Residual fuel oil No. 6', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.977, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.0000385],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 11.27, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.000146],
        ], 'note' => 'IPCC 77400 kgCO2/TJ, NCV 38.5 GJ/kL'],
        ['name' => 'Gasoline (Stationary)', 'desc' => 'Stationary engines', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.315, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.0000323],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 8.764, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.000122],
        ], 'note' => 'IPCC 69300 kgCO2/TJ'],
        ['name' => 'Jet Fuel (Stationary)', 'desc' => 'Jet kerosene in generators', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.534, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.0000349],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 9.593, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.000132],
        ], 'note' => 'IPCC 71500 kgCO2/TJ'],
        ['name' => 'Kerosene', 'desc' => 'Heaters, generators', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.538, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.0000349],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 9.607, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.000132],
        ], 'note' => 'IPCC 71900 kgCO2/TJ'],
        ['name' => 'Lignite Coal', 'desc' => 'Brown coal combustion', 'units' => [
            ['u' => 'kg', 'label' => 'Kilograms (kg)', 'co2' => 1.389, 'ch4' => 1, 'n2o' => 1.5, 'ncv' => 0.0000138],
            ['u' => 'tonnes', 'label' => 'Tonnes (t)', 'co2' => 1389, 'ch4' => 1, 'n2o' => 1.5, 'ncv' => 0.0138],
        ], 'note' => 'IPCC 101000 kgCO2/TJ, NCV 13.8 GJ/t'],
        ['name' => 'LPG / Propane', 'desc' => 'LPG combustion', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 1.555, 'ch4' => 1, 'n2o' => 0.1, 'ncv' => 0.0000261],
            ['u' => 'kg', 'label' => 'Kilograms (kg)', 'co2' => 2.983, 'ch4' => 1, 'n2o' => 0.1, 'ncv' => 0.0000473],
        ], 'note' => 'IPCC 63100 kgCO2/TJ'],
        ['name' => 'Naphtha', 'desc' => 'Naphtha combustion', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.630, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.0000359],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 9.955, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.000136],
        ], 'note' => 'IPCC 73300 kgCO2/TJ'],
        ['name' => 'Natural Gas', 'desc' => 'Boilers, furnaces, CHP', 'units' => [
            ['u' => 'm3', 'label' => 'Cubic Meters (m3)', 'co2' => 2.024, 'ch4' => 1, 'n2o' => 0.1, 'ncv' => 0.0000364],
            ['u' => 'kWh', 'label' => 'kWh', 'co2' => 0.202, 'ch4' => 1, 'n2o' => 0.1, 'ncv' => 0.0000036],
            ['u' => 'therms', 'label' => 'Therms', 'co2' => 5.31, 'ch4' => 1, 'n2o' => 0.1, 'ncv' => 0.0001055],
        ], 'note' => 'IPCC 56100 kgCO2/TJ'],
        ['name' => 'Peat', 'desc' => 'Peat fuel combustion', 'units' => [
            ['u' => 'kg', 'label' => 'Kilograms (kg)', 'co2' => 1.060, 'ch4' => 1, 'n2o' => 1.5, 'ncv' => 0.0000100],
            ['u' => 'tonnes', 'label' => 'Tonnes (t)', 'co2' => 1060, 'ch4' => 1, 'n2o' => 1.5, 'ncv' => 0.0100],
        ], 'note' => 'IPCC 106000 kgCO2/TJ, NCV 10.0 GJ/t'],
        ['name' => 'Petroleum Coke', 'desc' => 'Pet coke combustion', 'units' => [
            ['u' => 'kg', 'label' => 'Kilograms (kg)', 'co2' => 3.510, 'ch4' => 1, 'n2o' => 1.5, 'ncv' => 0.0000360],
            ['u' => 'tonnes', 'label' => 'Tonnes (t)', 'co2' => 3510, 'ch4' => 1, 'n2o' => 1.5, 'ncv' => 0.0360],
        ], 'note' => 'IPCC 97500 kgCO2/TJ, NCV 36.0 GJ/t'],
        ['name' => 'Sub-bituminous Coal', 'desc' => 'Sub-bituminous combustion', 'units' => [
            ['u' => 'kg', 'label' => 'Kilograms (kg)', 'co2' => 1.676, 'ch4' => 1, 'n2o' => 1.5, 'ncv' => 0.0000174],
            ['u' => 'tonnes', 'label' => 'Tonnes (t)', 'co2' => 1676, 'ch4' => 1, 'n2o' => 1.5, 'ncv' => 0.0174],
        ], 'note' => 'IPCC 96100 kgCO2/TJ, NCV 17.4 GJ/t'],
        ['name' => 'Waste Oil', 'desc' => 'Waste oil combustion', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.698, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.0000368],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 10.21, 'ch4' => 3, 'n2o' => 0.6, 'ncv' => 0.000139],
        ], 'note' => 'IPCC 73300 kgCO2/TJ'],
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
        ['name' => 'Fleet - Battery Electric', 'desc' => 'BEV company cars', 'units' => [
            ['u' => 'km', 'label' => 'Kilometers (km)', 'co2' => 0, 'ch4' => 0, 'n2o' => 0, 'ncv' => 0],
        ], 'note' => 'Zero direct Scope 1 emissions'],
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
        ['name' => 'Fleet - Hybrid (Diesel)', 'desc' => 'Hybrid diesel cars', 'units' => [
            ['u' => 'km', 'label' => 'Kilometers (km)', 'co2' => 0.140, 'ch4' => 0, 'n2o' => 0, 'ncv' => 0],
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.676, 'ch4' => 3.9, 'n2o' => 3.9, 'ncv' => 0.0000359],
        ], 'note' => 'km: DEFRA est. ~20% less than diesel; fuel: IPCC'],
        ['name' => 'Fleet - Hybrid (Petrol)', 'desc' => 'Hybrid petrol cars', 'units' => [
            ['u' => 'km', 'label' => 'Kilometers (km)', 'co2' => 0.128, 'ch4' => 0, 'n2o' => 0, 'ncv' => 0],
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.315, 'ch4' => 3.5, 'n2o' => 5.7, 'ncv' => 0.0000323],
        ], 'note' => 'km: DEFRA 2025; fuel: IPCC 69300 kgCO2/TJ'],
        ['name' => 'Fleet - LNG', 'desc' => 'LNG fleet vehicles', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 1.178, 'ch4' => 92, 'n2o' => 3, 'ncv' => 0.0000210],
            ['u' => 'kg', 'label' => 'Kilograms (kg)', 'co2' => 2.743, 'ch4' => 92, 'n2o' => 3, 'ncv' => 0.0000480],
        ], 'note' => 'IPCC 56100 kgCO2/TJ (same as CNG, liquid form)'],
        ['name' => 'Fleet - LPG', 'desc' => 'LPG vehicles', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 1.555, 'ch4' => 62, 'n2o' => 0.2, 'ncv' => 0.0000261],
        ], 'note' => 'IPCC 63100 kgCO2/TJ mobile'],
        ['name' => 'Fleet - Plug-in Hybrid', 'desc' => 'PHEV company cars', 'units' => [
            ['u' => 'km', 'label' => 'Kilometers (km)', 'co2' => 0.137, 'ch4' => 0, 'n2o' => 0, 'ncv' => 0],
        ], 'note' => 'DEFRA 2025 avg real-world PHEV'],
        ['name' => 'Forklift - Diesel', 'desc' => 'Diesel forklifts', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.676, 'ch4' => 4.15, 'n2o' => 28.6, 'ncv' => 0.0000359],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 10.13, 'ch4' => 4.15, 'n2o' => 28.6, 'ncv' => 0.000136],
        ], 'note' => 'IPCC off-road Table 3.3.1'],
        ['name' => 'Forklift - LPG', 'desc' => 'LPG forklifts', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 1.555, 'ch4' => 62, 'n2o' => 0.2, 'ncv' => 0.0000261],
        ], 'note' => 'IPCC 63100 kgCO2/TJ off-road'],
        ['name' => 'HGV - Diesel', 'desc' => 'Heavy goods vehicles', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.676, 'ch4' => 3.9, 'n2o' => 3.9, 'ncv' => 0.0000359],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 10.13, 'ch4' => 3.9, 'n2o' => 3.9, 'ncv' => 0.000136],
        ], 'note' => 'IPCC 74100 kgCO2/TJ heavy-duty on-road'],
        ['name' => 'Light Duty Truck - Diesel', 'desc' => 'Diesel light-duty truck', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.676, 'ch4' => 3.9, 'n2o' => 3.9, 'ncv' => 0.0000359],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 10.13, 'ch4' => 3.9, 'n2o' => 3.9, 'ncv' => 0.000136],
        ], 'note' => 'IPCC 74100 kgCO2/TJ mobile'],
        ['name' => 'Light Duty Truck - Gasoline', 'desc' => 'Gasoline light-duty truck', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.315, 'ch4' => 3.5, 'n2o' => 5.7, 'ncv' => 0.0000323],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 8.764, 'ch4' => 3.5, 'n2o' => 5.7, 'ncv' => 0.000122],
        ], 'note' => 'IPCC 69300 kgCO2/TJ mobile'],
        ['name' => 'Marine - Diesel', 'desc' => 'Vessel diesel', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.676, 'ch4' => 7, 'n2o' => 2, 'ncv' => 0.0000359],
            ['u' => 'tonnes', 'label' => 'Tonnes (t)', 'co2' => 3188, 'ch4' => 7, 'n2o' => 2, 'ncv' => 0.0431],
        ], 'note' => 'IPCC 74100 kgCO2/TJ marine'],
        ['name' => 'Marine - HFO', 'desc' => 'HFO vessels', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 3.114, 'ch4' => 7, 'n2o' => 2, 'ncv' => 0.0000404],
            ['u' => 'tonnes', 'label' => 'Tonnes (t)', 'co2' => 3206, 'ch4' => 7, 'n2o' => 2, 'ncv' => 0.0404],
        ], 'note' => 'IPCC 77400 kgCO2/TJ marine'],
        ['name' => 'Marine - LNG', 'desc' => 'LNG marine vessels', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 1.178, 'ch4' => 7, 'n2o' => 2, 'ncv' => 0.0000210],
            ['u' => 'kg', 'label' => 'Kilograms (kg)', 'co2' => 2.743, 'ch4' => 7, 'n2o' => 2, 'ncv' => 0.0000480],
        ], 'note' => 'IPCC 56100 kgCO2/TJ marine CH4/N2O'],
        ['name' => 'Medium/Heavy Duty Truck - Diesel', 'desc' => 'Medium/heavy-duty truck', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.676, 'ch4' => 3.9, 'n2o' => 3.9, 'ncv' => 0.0000359],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 10.13, 'ch4' => 3.9, 'n2o' => 3.9, 'ncv' => 0.000136],
        ], 'note' => 'IPCC 74100 kgCO2/TJ heavy-duty on-road'],
        ['name' => 'Motorcycle - Petrol', 'desc' => 'Company motorcycles', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.315, 'ch4' => 80, 'n2o' => 2, 'ncv' => 0.0000323],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 8.764, 'ch4' => 80, 'n2o' => 2, 'ncv' => 0.000122],
        ], 'note' => 'IPCC Table 3.2.2 motorcycle 4-stroke'],
        ['name' => 'Off-road - Diesel', 'desc' => 'Construction/mining', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.676, 'ch4' => 4.15, 'n2o' => 28.6, 'ncv' => 0.0000359],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 10.13, 'ch4' => 4.15, 'n2o' => 28.6, 'ncv' => 0.000136],
        ], 'note' => 'IPCC off-road Table 3.3.1'],
        ['name' => 'Off-road - Gasoline', 'desc' => 'Gasoline off-road equipment', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.315, 'ch4' => 33, 'n2o' => 3.2, 'ncv' => 0.0000323],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 8.764, 'ch4' => 33, 'n2o' => 3.2, 'ncv' => 0.000122],
        ], 'note' => 'IPCC off-road Table 3.3.1 gasoline 4-stroke'],
        ['name' => 'Rail - Diesel', 'desc' => 'Company locomotives', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.676, 'ch4' => 4.15, 'n2o' => 28.6, 'ncv' => 0.0000359],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 10.13, 'ch4' => 4.15, 'n2o' => 28.6, 'ncv' => 0.000136],
        ], 'note' => 'IPCC rail diesel'],
        ['name' => 'Van - Diesel', 'desc' => 'Diesel van', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.676, 'ch4' => 3.9, 'n2o' => 3.9, 'ncv' => 0.0000359],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 10.13, 'ch4' => 3.9, 'n2o' => 3.9, 'ncv' => 0.000136],
            ['u' => 'km', 'label' => 'Kilometers (km)', 'co2' => 0.317, 'ch4' => 0, 'n2o' => 0, 'ncv' => 0],
        ], 'note' => 'Fuel: IPCC 74100; km: DEFRA 2025'],
        ['name' => 'Van - Electric', 'desc' => 'Electric van', 'units' => [
            ['u' => 'km', 'label' => 'Kilometers (km)', 'co2' => 0, 'ch4' => 0, 'n2o' => 0, 'ncv' => 0],
        ], 'note' => 'Zero direct Scope 1 emissions'],
        ['name' => 'Van - Petrol', 'desc' => 'Petrol van', 'units' => [
            ['u' => 'liters', 'label' => 'Liters (L)', 'co2' => 2.315, 'ch4' => 3.5, 'n2o' => 5.7, 'ncv' => 0.0000323],
            ['u' => 'gallons', 'label' => 'Gallons (gal)', 'co2' => 8.764, 'ch4' => 3.5, 'n2o' => 5.7, 'ncv' => 0.000122],
            ['u' => 'km', 'label' => 'Kilometers (km)', 'co2' => 0.273, 'ch4' => 0, 'n2o' => 0, 'ncv' => 0],
        ], 'note' => 'Fuel: IPCC 69300; km: DEFRA 2025'],
    ],
    'fugitive' => [
        ['name' => 'C2F6 (PFC-116)', 'desc' => 'C2F6 fugitive emissions', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 12200, 'note' => 'C2F6 GWP=12200 AR5'],
        ['name' => 'CF4 (PFC-14)', 'desc' => 'CF4 fugitive emissions', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 7390, 'note' => 'CF4 GWP=7390 AR5'],
        ['name' => 'CO2 from Processes', 'desc' => 'Process CO2 (cement, lime, glass)', 'units' => [
            ['u' => 'kg', 'label' => 'Kilograms (kg)'],
            ['u' => 'tonnes', 'label' => 'Tonnes (t)'],
        ], 'isFug' => true, 'gwp' => 1, 'gwpTonnes' => 1000, 'note' => 'Direct CO2, GWP=1'],
        ['name' => 'Fire Suppression (Halon)', 'desc' => 'Halon systems', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 0, 'note' => 'Halon ODP substance'],
        ['name' => 'Fire Suppression (HFCs)', 'desc' => 'HFC fire systems', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 3220, 'note' => 'HFC-227ea GWP=3220 AR5'],
        ['name' => 'Methane Leakage', 'desc' => 'Gas pipe leaks', 'units' => [
            ['u' => 'kg', 'label' => 'Kilograms (kg)'],
            ['u' => 'm3', 'label' => 'Cubic Meters (m3)'],
        ], 'isFug' => true, 'gwp' => 28, 'gwpM3' => 18.76, 'note' => 'CH4 GWP=28 AR5'],
        ['name' => 'N2O from Processes', 'desc' => 'Industrial N2O', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 265, 'note' => 'N2O GWP=265 AR5'],
        ['name' => 'NF3 (Semiconductor)', 'desc' => 'NF3 semiconductor mfg', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 17200, 'note' => 'NF3 GWP=17200 AR5'],
        ['name' => 'PFCs (Aluminium)', 'desc' => 'PFC production', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 7390, 'note' => 'CF4 GWP=7390 AR5'],
        ['name' => 'Refrigerant - R-1234yf', 'desc' => 'HFO-1234yf ultra-low GWP', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 4, 'note' => 'R-1234yf GWP<4 AR5'],
        ['name' => 'Refrigerant - R-134a', 'desc' => 'HFC-134a leakage', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 1430, 'note' => 'R-134a GWP=1430 AR5'],
        ['name' => 'Refrigerant - R-22', 'desc' => 'HCFC-22 leakage', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 1810, 'note' => 'R-22 GWP=1810 AR5'],
        ['name' => 'Refrigerant - R-290 (Propane)', 'desc' => 'Natural refrigerant', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 3, 'note' => 'R-290 GWP=3 AR5'],
        ['name' => 'Refrigerant - R-32', 'desc' => 'HFC-32 leakage', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 675, 'note' => 'R-32 GWP=675 AR5'],
        ['name' => 'Refrigerant - R-404A', 'desc' => 'R-404A blend leakage', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 3922, 'note' => 'R-404A GWP=3922 AR5'],
        ['name' => 'Refrigerant - R-407C', 'desc' => 'R-407C blend leakage', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 1774, 'note' => 'R-407C GWP=1774 AR5'],
        ['name' => 'Refrigerant - R-410A', 'desc' => 'R-410A blend leakage', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 2088, 'note' => 'R-410A GWP=2088 AR5'],
        ['name' => 'Refrigerant - R-448A', 'desc' => 'R-448A blend leakage', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 1387, 'note' => 'R-448A GWP=1387 AR5'],
        ['name' => 'Refrigerant - R-449A', 'desc' => 'R-449A blend leakage', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 1397, 'note' => 'R-449A GWP=1397 AR5'],
        ['name' => 'Refrigerant - R-454B', 'desc' => 'R-454B HFO blend', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 466, 'note' => 'R-454B GWP=466 AR5'],
        ['name' => 'Refrigerant - R-507A', 'desc' => 'R-507A blend leakage', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 3985, 'note' => 'R-507A GWP=3985 AR5'],
        ['name' => 'Refrigerant - R-717 (Ammonia)', 'desc' => 'Natural refrigerant', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 0, 'note' => 'Ammonia GWP=0'],
        ['name' => 'Refrigerant - R-744 (CO2)', 'desc' => 'CO2 natural refrigerant', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 1, 'note' => 'R-744 GWP=1'],
        ['name' => 'Refrigerant (CFCs)', 'desc' => 'CFC HVAC leaks', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 10900, 'note' => 'CFC-12 GWP=10900'],
        ['name' => 'Refrigerant (HCFCs)', 'desc' => 'HCFC e.g. R-22', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 1810, 'note' => 'HCFC-22 GWP=1810'],
        ['name' => 'Refrigerant (HFCs)', 'desc' => 'HFC e.g. R-410A', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 2088, 'note' => 'R-410A GWP=2088'],
        ['name' => 'Refrigerant (HFOs)', 'desc' => 'HFO/natural refrig', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 4, 'note' => 'HFO GWP<4'],
        ['name' => 'SF6 (Electrical)', 'desc' => 'SF6 switchgear', 'units' => [['u' => 'kg', 'label' => 'Kilograms (kg)']], 'isFug' => true, 'gwp' => 23500, 'note' => 'SF6 GWP=23500 AR5'],
    ],
];
