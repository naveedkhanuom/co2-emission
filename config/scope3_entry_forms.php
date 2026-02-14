<?php

/**
 * Scope 3 Entry — category-specific form definitions (fields and defaults).
 * sort_order 1–15 maps to GHG Protocol Scope 3 categories.
 * Calculation logic is in scope3_entry/script (per method: spend, travel, waste, distance, etc.).
 */
return [
    1 => [
        'method' => 'spend',
        'methodLabel' => 'Spend-Based',
        'info' => 'Multiply procurement spend by emission factor (kg CO₂e per $).',
        'fields' => [
            ['key' => 'category_name', 'label' => 'Spend Category', 'type' => 'text', 'placeholder' => 'e.g. Office Supplies, IT Equipment'],
            ['key' => 'spend', 'label' => 'Spend ($)', 'type' => 'number', 'placeholder' => 'e.g. 500000'],
            ['key' => 'ef', 'label' => 'Emission Factor (kg CO₂e/$)', 'type' => 'number', 'placeholder' => 'e.g. 0.43', 'step' => '0.001', 'defaultValue' => '0.43'],
        ],
    ],
    2 => [
        'method' => 'spend',
        'methodLabel' => 'Spend-Based',
        'info' => 'Capital expenditure × emission factor.',
        'fields' => [
            ['key' => 'item', 'label' => 'Capital Good', 'type' => 'text', 'placeholder' => 'e.g. Server rack, Vehicle fleet'],
            ['key' => 'spend', 'label' => 'Total Cost ($)', 'type' => 'number', 'placeholder' => 'e.g. 250000'],
            ['key' => 'ef', 'label' => 'Emission Factor (kg CO₂e/$)', 'type' => 'number', 'placeholder' => 'e.g. 0.55', 'step' => '0.001', 'defaultValue' => '0.55'],
        ],
    ],
    3 => [
        'method' => 'activity',
        'methodLabel' => 'Activity-Based',
        'info' => 'Consumption × emission factor (e.g. electricity T&D, WTT).',
        'fields' => [
            ['key' => 'source', 'label' => 'Energy Source', 'type' => 'select', 'options' => ['Electricity (T&D losses)', 'Natural Gas (WTT)', 'Diesel (WTT)', 'Gasoline (WTT)', 'Other']],
            ['key' => 'quantity', 'label' => 'Quantity', 'type' => 'number', 'placeholder' => 'kWh, litres, or therms'],
            ['key' => 'unit', 'label' => 'Unit', 'type' => 'select', 'options' => ['kWh', 'litres', 'therms', 'gallons', 'tonnes']],
            ['key' => 'ef', 'label' => 'Emission Factor (kg CO₂e/unit)', 'type' => 'number', 'placeholder' => 'e.g. 0.019', 'step' => '0.0001', 'defaultValue' => '0.019'],
        ],
    ],
    4 => [
        'method' => 'distance',
        'methodLabel' => 'Distance-Based',
        'info' => 'Weight × distance × factor (tonne-km).',
        'fields' => [
            ['key' => 'mode', 'label' => 'Transport Mode', 'type' => 'select', 'options' => ['Road (truck)', 'Rail', 'Sea (container)', 'Air freight', 'Pipeline']],
            ['key' => 'weight', 'label' => 'Weight (tonnes)', 'type' => 'number', 'placeholder' => 'e.g. 100'],
            ['key' => 'distance', 'label' => 'Distance (km)', 'type' => 'number', 'placeholder' => 'e.g. 500'],
            ['key' => 'ef', 'label' => 'Emission Factor (kg CO₂e/tonne-km)', 'type' => 'number', 'placeholder' => 'e.g. 0.107', 'step' => '0.001', 'defaultValue' => '0.107'],
        ],
    ],
    5 => [
        'method' => 'waste',
        'methodLabel' => 'Waste-Type',
        'info' => 'Weight × emission factor by disposal method.',
        'fields' => [
            ['key' => 'waste_type', 'label' => 'Waste Type', 'type' => 'select', 'options' => ['General waste', 'Paper/Cardboard', 'Plastics', 'Food/Organic', 'Glass', 'Metals', 'E-waste', 'Construction']],
            ['key' => 'disposal', 'label' => 'Disposal Method', 'type' => 'select', 'options' => ['Landfill', 'Recycling', 'Incineration', 'Composting', 'Anaerobic digestion']],
            ['key' => 'weight', 'label' => 'Weight (tonnes/year)', 'type' => 'number', 'placeholder' => 'e.g. 50'],
            ['key' => 'ef', 'label' => 'Emission Factor (kg CO₂e/tonne)', 'type' => 'number', 'placeholder' => 'e.g. 467', 'step' => '0.1', 'defaultValue' => '467'],
        ],
    ],
    6 => [
        'method' => 'travel',
        'methodLabel' => 'Travel',
        'info' => 'Quantity × emission factor (e.g. passenger-km, nights).',
        'fields' => [
            ['key' => 'travel_type', 'label' => 'Travel Type', 'type' => 'select', 'options' => ['Flight — Short haul (<1500 km)', 'Flight — Medium haul', 'Flight — Long haul (>3700 km)', 'Rail', 'Car rental / Taxi', 'Hotel nights']],
            ['key' => 'quantity', 'label' => 'Quantity', 'type' => 'number', 'placeholder' => 'km, nights, or trips'],
            ['key' => 'unit', 'label' => 'Unit', 'type' => 'select', 'options' => ['passenger-km', 'nights', 'trips', 'km']],
            ['key' => 'ef', 'label' => 'Emission Factor (kg CO₂e/unit)', 'type' => 'number', 'placeholder' => 'e.g. 0.255', 'step' => '0.001', 'defaultValue' => '0.255'],
        ],
    ],
    7 => [
        'method' => 'commute',
        'methodLabel' => 'Commuting',
        'info' => 'Distance × employees × days × factor per mode.',
        'fields' => [
            ['key' => 'mode', 'label' => 'Transport Mode', 'type' => 'select', 'options' => ['Car — Gasoline', 'Car — Diesel', 'Car — Hybrid', 'Car — Electric', 'Bus', 'Train / Rail', 'Metro', 'Motorcycle', 'Bicycle', 'Walking']],
            ['key' => 'distance_km', 'label' => 'Round-trip Distance (km)', 'type' => 'number', 'placeholder' => 'e.g. 20'],
            ['key' => 'employees', 'label' => 'Number of Employees', 'type' => 'number', 'placeholder' => 'e.g. 50'],
            ['key' => 'days_per_year', 'label' => 'Working Days/Year', 'type' => 'number', 'placeholder' => 'e.g. 230', 'defaultValue' => '230'],
            ['key' => 'ef', 'label' => 'Emission Factor (kg CO₂e/passenger-km)', 'type' => 'number', 'placeholder' => 'e.g. 0.192', 'step' => '0.001', 'defaultValue' => '0.192'],
        ],
    ],
    8 => [
        'method' => 'activity',
        'methodLabel' => 'Asset-Based',
        'info' => 'Quantity (m², units, kWh) × emission factor.',
        'fields' => [
            ['key' => 'asset', 'label' => 'Leased Asset', 'type' => 'text', 'placeholder' => 'e.g. Office space, Fleet vehicles'],
            ['key' => 'quantity', 'label' => 'Quantity', 'type' => 'number', 'placeholder' => 'e.g. 2000'],
            ['key' => 'unit', 'label' => 'Unit', 'type' => 'select', 'options' => ['m²', 'units', 'hours', 'kWh']],
            ['key' => 'ef', 'label' => 'Emission Factor (kg CO₂e/unit)', 'type' => 'number', 'placeholder' => 'e.g. 35', 'step' => '0.01', 'defaultValue' => '35'],
        ],
    ],
    9 => [
        'method' => 'distance',
        'methodLabel' => 'Distance-Based',
        'info' => 'Weight × distance × factor (outbound logistics).',
        'fields' => [
            ['key' => 'mode', 'label' => 'Transport Mode', 'type' => 'select', 'options' => ['Road (truck)', 'Rail', 'Sea (container)', 'Air freight', 'Last-mile delivery']],
            ['key' => 'weight', 'label' => 'Weight (tonnes)', 'type' => 'number', 'placeholder' => 'e.g. 50'],
            ['key' => 'distance', 'label' => 'Distance (km)', 'type' => 'number', 'placeholder' => 'e.g. 200'],
            ['key' => 'ef', 'label' => 'Emission Factor (kg CO₂e/tonne-km)', 'type' => 'number', 'placeholder' => 'e.g. 0.107', 'step' => '0.001', 'defaultValue' => '0.107'],
        ],
    ],
    10 => [
        'method' => 'activity',
        'methodLabel' => 'Activity-Based',
        'info' => 'Quantity processed × emission factor.',
        'fields' => [
            ['key' => 'product', 'label' => 'Product', 'type' => 'text', 'placeholder' => 'e.g. Steel sheets, Chemical feedstock'],
            ['key' => 'quantity', 'label' => 'Quantity (units or tonnes)', 'type' => 'number', 'placeholder' => 'e.g. 10000'],
            ['key' => 'ef', 'label' => 'Emission Factor (kg CO₂e/unit)', 'type' => 'number', 'placeholder' => 'e.g. 12.5', 'step' => '0.01', 'defaultValue' => '12.5'],
        ],
    ],
    11 => [
        'method' => 'use',
        'methodLabel' => 'Use of Sold Products',
        'info' => 'Units × energy per use × lifetime × grid EF.',
        'fields' => [
            ['key' => 'product', 'label' => 'Product', 'type' => 'text', 'placeholder' => 'e.g. Electric appliance, Vehicle'],
            ['key' => 'units_sold', 'label' => 'Units Sold', 'type' => 'number', 'placeholder' => 'e.g. 5000'],
            ['key' => 'energy_per_use', 'label' => 'Energy/Year per Unit (kWh)', 'type' => 'number', 'placeholder' => 'e.g. 300'],
            ['key' => 'lifetime', 'label' => 'Lifetime (years)', 'type' => 'number', 'placeholder' => 'e.g. 10'],
            ['key' => 'ef', 'label' => 'Grid EF (kg CO₂e/kWh)', 'type' => 'number', 'placeholder' => 'e.g. 0.42', 'step' => '0.001', 'defaultValue' => '0.42'],
        ],
    ],
    12 => [
        'method' => 'waste',
        'methodLabel' => 'Waste-Type',
        'info' => 'Product weight × disposal factor.',
        'fields' => [
            ['key' => 'material', 'label' => 'Primary Material', 'type' => 'select', 'options' => ['Plastics', 'Metals', 'Paper/Cardboard', 'Glass', 'Electronics', 'Textiles', 'Mixed']],
            ['key' => 'disposal', 'label' => 'Likely Disposal', 'type' => 'select', 'options' => ['Landfill', 'Recycling', 'Incineration', 'Composting']],
            ['key' => 'weight', 'label' => 'Total Product Weight (tonnes)', 'type' => 'number', 'placeholder' => 'e.g. 200'],
            ['key' => 'ef', 'label' => 'Emission Factor (kg CO₂e/tonne)', 'type' => 'number', 'placeholder' => 'e.g. 300', 'step' => '0.1', 'defaultValue' => '300'],
        ],
    ],
    13 => [
        'method' => 'activity',
        'methodLabel' => 'Asset-Based',
        'info' => 'Quantity × emission factor (downstream leased assets).',
        'fields' => [
            ['key' => 'asset', 'label' => 'Leased Asset', 'type' => 'text', 'placeholder' => 'e.g. Commercial property, Equipment'],
            ['key' => 'quantity', 'label' => 'Quantity', 'type' => 'number', 'placeholder' => 'e.g. 5000'],
            ['key' => 'unit', 'label' => 'Unit', 'type' => 'select', 'options' => ['m²', 'units', 'kWh', 'hours']],
            ['key' => 'ef', 'label' => 'Emission Factor (kg CO₂e/unit)', 'type' => 'number', 'placeholder' => 'e.g. 35', 'step' => '0.01', 'defaultValue' => '35'],
        ],
    ],
    14 => [
        'method' => 'spend',
        'methodLabel' => 'Franchise-Based',
        'info' => 'Revenue or energy × emission factor.',
        'fields' => [
            ['key' => 'franchise', 'label' => 'Franchise / Region', 'type' => 'text', 'placeholder' => 'e.g. US West Coast stores'],
            ['key' => 'quantity', 'label' => 'Energy or Revenue', 'type' => 'number', 'placeholder' => 'e.g. 1000000'],
            ['key' => 'unit', 'label' => 'Metric', 'type' => 'select', 'options' => ['kWh', '$ revenue', 'm² floor area']],
            ['key' => 'ef', 'label' => 'Emission Factor (kg CO₂e/unit)', 'type' => 'number', 'placeholder' => 'e.g. 0.42', 'step' => '0.001', 'defaultValue' => '0.42'],
        ],
    ],
    15 => [
        'method' => 'investment',
        'methodLabel' => 'Investment-Based',
        'info' => 'Ownership % × investee emissions (tCO₂e).',
        'fields' => [
            ['key' => 'investee', 'label' => 'Investee / Fund', 'type' => 'text', 'placeholder' => 'e.g. Company A, Green Bond Fund'],
            ['key' => 'invested', 'label' => 'Amount Invested ($)', 'type' => 'number', 'placeholder' => 'e.g. 5000000'],
            ['key' => 'ownership', 'label' => 'Ownership %', 'type' => 'number', 'placeholder' => 'e.g. 15', 'step' => '0.1'],
            ['key' => 'investee_emissions', 'label' => 'Investee Emissions (tCO₂e)', 'type' => 'number', 'placeholder' => 'e.g. 25000'],
        ],
    ],
];
