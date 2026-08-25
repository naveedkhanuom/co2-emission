<?php

/**
 * Clarifying questions for the Boundary Advisor.
 *
 * These are the questions whose answer actually CHANGES the boundary — not a
 * general survey. Every one is multiple-choice with a "Not sure" escape,
 * because the people answering are ordinary facility, finance and ops staff,
 * not carbon accountants (see OnboardingController).
 *
 * Each option carries an `implies` block that the deterministic fallback reads
 * directly, so the advisor still produces a sensible boundary with no AI
 * provider configured:
 *
 *   scopes             int[]   scopes to switch on
 *   categories         int[]   Scope 3 category numbers (1-15) to mark relevant
 *   exclude_categories int[]   Scope 3 category numbers to mark NOT relevant
 *   tags               string[] free hints handed to the AI prompt
 *
 * Questions are assembled as: common + industry + sub-industry, capped at
 * `max_questions`. Keep each list short — every extra question is friction on
 * the one screen that decides whether a user finishes setup.
 */
return [

    // Hard cap on the total shown, including any the AI adds.
    'max_questions' => 8,

    // Most an AI provider may append to the deterministic set.
    'max_ai_questions' => 3,

    /*
    |--------------------------------------------------------------------------
    | Asked of every company
    |--------------------------------------------------------------------------
    */
    'common' => [

        'premises' => [
            'question' => 'Do you own or rent the buildings you work in?',
            'help' => 'This decides whether the building’s energy is yours to report, or your landlord’s.',
            'options' => [
                'own' => ['label' => 'We own them', 'implies' => ['scopes' => [1, 2]]],
                'lease_we_pay' => ['label' => 'We rent and we pay the utility bills', 'implies' => ['scopes' => [1, 2]]],
                'lease_landlord_pays' => ['label' => 'We rent and the landlord pays the bills', 'implies' => ['categories' => [8], 'tags' => ['landlord_controlled_energy']]],
                'mixed' => ['label' => 'A mix of both', 'implies' => ['scopes' => [1, 2], 'categories' => [8]]],
                'not_sure' => ['label' => 'Not sure', 'implies' => ['scopes' => [1, 2], 'tags' => ['premises_unclear']]],
            ],
        ],

        'vehicles' => [
            'question' => 'How do your people and goods move around?',
            'help' => 'Vehicles you own are counted differently from taxis, couriers or staff cars.',
            'options' => [
                'own_fleet' => ['label' => 'We own or lease our own vehicles', 'implies' => ['scopes' => [1], 'tags' => ['owns_fleet']]],
                'third_party' => ['label' => 'We use couriers, taxis and third-party transport', 'implies' => ['categories' => [4, 9]]],
                'staff_own_cars' => ['label' => 'Staff use their own cars for work', 'implies' => ['categories' => [6, 7]]],
                'both' => ['label' => 'Both — we have vehicles and use third parties', 'implies' => ['scopes' => [1], 'categories' => [4, 9], 'tags' => ['owns_fleet']]],
                'none' => ['label' => 'Neither — we do not move goods or people', 'implies' => ['exclude_categories' => [4, 9]]],
            ],
        ],

        'onsite_fuel' => [
            'question' => 'Do you burn any fuel on your own sites?',
            'help' => 'Generators, boilers, furnaces, kitchen gas, heating — anything with a flame or an engine.',
            'options' => [
                'generators' => ['label' => 'Yes — backup or primary generators', 'implies' => ['scopes' => [1], 'tags' => ['diesel_generators']]],
                'heating' => ['label' => 'Yes — boilers, heating or kitchen gas', 'implies' => ['scopes' => [1], 'tags' => ['stationary_combustion']]],
                'process' => ['label' => 'Yes — furnaces or kilns for production', 'implies' => ['scopes' => [1], 'tags' => ['process_heat']]],
                'no' => ['label' => 'No, we burn nothing on site', 'implies' => []],
                'not_sure' => ['label' => 'Not sure', 'implies' => ['scopes' => [1], 'tags' => ['fuel_unclear']]],
            ],
        ],

        'cooling' => [
            'question' => 'Do you have air-conditioning, chillers or refrigeration?',
            'help' => 'Refrigerant gases leak slowly from this equipment. A small leak can equal thousands of litres of fuel.',
            'options' => [
                'own_equipment' => ['label' => 'Yes, and we own/maintain the equipment', 'implies' => ['scopes' => [1], 'tags' => ['fugitive_refrigerants']]],
                'district' => ['label' => 'Yes, but we buy district cooling from a provider', 'implies' => ['scopes' => [2], 'tags' => ['district_cooling']]],
                'landlord' => ['label' => 'Yes, but the landlord owns and maintains it', 'implies' => ['categories' => [8]]],
                'no' => ['label' => 'No cooling or refrigeration', 'implies' => []],
                'not_sure' => ['label' => 'Not sure', 'implies' => ['scopes' => [1], 'tags' => ['refrigerant_unclear']]],
            ],
        ],

        'procurement' => [
            'question' => 'What do you mainly buy from suppliers?',
            'help' => 'Emissions are built into everything you purchase, before it ever reaches you.',
            'options' => [
                'materials' => ['label' => 'Physical materials or raw goods', 'implies' => ['categories' => [1, 2, 4], 'tags' => ['material_intensive']]],
                'products' => ['label' => 'Finished products we resell', 'implies' => ['categories' => [1, 4, 9]]],
                'services' => ['label' => 'Mostly services and professional fees', 'implies' => ['categories' => [1], 'tags' => ['service_business']]],
                'both' => ['label' => 'A mix of goods and services', 'implies' => ['categories' => [1, 2, 4]]],
                'not_sure' => ['label' => 'Not sure', 'implies' => ['categories' => [1], 'tags' => ['procurement_unclear']]],
            ],
        ],

        'products_in_use' => [
            'question' => 'After a customer buys from you, does your product use energy or need disposal?',
            'help' => 'A machine that runs on electricity, or packaging that gets thrown away, keeps emitting after you sell it.',
            'options' => [
                'uses_energy' => ['label' => 'Yes — it consumes fuel or electricity in use', 'implies' => ['categories' => [11, 12], 'tags' => ['energy_using_product']]],
                'disposal' => ['label' => 'It does not use energy, but it gets thrown away', 'implies' => ['categories' => [12]]],
                'service' => ['label' => 'We sell a service, not a physical product', 'implies' => ['exclude_categories' => [10, 11, 12]]],
                'not_sure' => ['label' => 'Not sure', 'implies' => ['tags' => ['product_use_unclear']]],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Industry-specific — the question that industry always gets wrong
    |--------------------------------------------------------------------------
    */
    'by_industry' => [

        'construction' => [
            'materials_buyer' => [
                'question' => 'Who buys the concrete, steel and other materials for your projects?',
                'help' => 'Materials bought by a subcontractor on your behalf still count as yours.',
                'options' => [
                    'we_buy' => ['label' => 'We buy them directly', 'implies' => ['categories' => [1], 'tags' => ['direct_material_purchase']]],
                    'subcontractor' => ['label' => 'Our subcontractors buy them', 'implies' => ['categories' => [1], 'tags' => ['subcontractor_materials']]],
                    'client_supplies' => ['label' => 'The client supplies them', 'implies' => ['tags' => ['client_supplied_materials']]],
                    'mixed' => ['label' => 'Depends on the project', 'implies' => ['categories' => [1], 'tags' => ['subcontractor_materials']]],
                ],
            ],
            'site_power' => [
                'question' => 'How do you power your construction sites?',
                'help' => 'Temporary site power is usually diesel, and usually forgotten.',
                'options' => [
                    'generators' => ['label' => 'Diesel generators', 'implies' => ['scopes' => [1], 'tags' => ['site_generators']]],
                    'grid' => ['label' => 'Temporary grid connection', 'implies' => ['scopes' => [2]]],
                    'both' => ['label' => 'Both, depending on the site', 'implies' => ['scopes' => [1, 2], 'tags' => ['site_generators']]],
                ],
            ],
        ],

        'healthcare' => [
            'anaesthetic' => [
                'question' => 'Do you use anaesthetic gases or medical gases?',
                'help' => 'Desflurane and nitrous oxide have very high warming impact — one cylinder can outweigh a year of driving.',
                'options' => [
                    'yes_theatre' => ['label' => 'Yes — operating theatres', 'implies' => ['scopes' => [1], 'tags' => ['anaesthetic_gases']]],
                    'yes_n2o' => ['label' => 'Yes — nitrous oxide for pain relief', 'implies' => ['scopes' => [1], 'tags' => ['anaesthetic_gases']]],
                    'no' => ['label' => 'No', 'implies' => []],
                    'not_sure' => ['label' => 'Not sure', 'implies' => ['scopes' => [1], 'tags' => ['anaesthetic_unclear']]],
                ],
            ],
            'clinical_waste' => [
                'question' => 'How is your clinical waste treated?',
                'help' => 'Incinerated clinical waste carries a far higher factor than general waste.',
                'options' => [
                    'incineration' => ['label' => 'Incinerated by a licensed contractor', 'implies' => ['categories' => [5], 'tags' => ['clinical_incineration']]],
                    'autoclave' => ['label' => 'Autoclaved then landfilled', 'implies' => ['categories' => [5]]],
                    'not_sure' => ['label' => 'Not sure', 'implies' => ['categories' => [5], 'tags' => ['waste_route_unclear']]],
                ],
            ],
        ],

        'transportation' => [
            'who_drives' => [
                'question' => 'Who drives the vehicles you own?',
                'help' => 'This is the single most important question for your boundary. Fuel burned by YOUR staff is direct; fuel burned by a CUSTOMER in your vehicle is not.',
                'options' => [
                    'our_staff' => ['label' => 'Our own staff or drivers', 'implies' => ['scopes' => [1], 'tags' => ['staff_operated_fleet']]],
                    'customers' => ['label' => 'Our customers — we rent or lease vehicles to them', 'implies' => ['categories' => [13], 'exclude_categories' => [], 'tags' => ['customer_operated_fleet', 'downstream_leased']]],
                    'both' => ['label' => 'Both', 'implies' => ['scopes' => [1], 'categories' => [13], 'tags' => ['customer_operated_fleet']]],
                    'contractors' => ['label' => 'Third-party contractors', 'implies' => ['categories' => [4, 9]]],
                ],
            ],
            'fleet_ownership' => [
                'question' => 'Do you own the vehicles/vessels you operate, or charter them?',
                'help' => 'Chartered capacity you do not operate sits in your value chain, not your direct emissions.',
                'options' => [
                    'owned' => ['label' => 'We own them', 'implies' => ['scopes' => [1]]],
                    'chartered' => ['label' => 'We charter or hire them', 'implies' => ['categories' => [4, 8], 'tags' => ['chartered_capacity']]],
                    'both' => ['label' => 'Both', 'implies' => ['scopes' => [1], 'categories' => [4, 8]]],
                ],
            ],
        ],

        'manufacturing' => [
            'process_emissions' => [
                'question' => 'Does your production release gases from the chemical reaction itself, not just from burning fuel?',
                'help' => 'Cement, lime, glass, steel and ammonia all release CO₂ from the process, separately from the fuel.',
                'options' => [
                    'yes' => ['label' => 'Yes', 'implies' => ['scopes' => [1], 'tags' => ['process_emissions']]],
                    'no' => ['label' => 'No — only fuel combustion', 'implies' => ['scopes' => [1]]],
                    'not_sure' => ['label' => 'Not sure', 'implies' => ['scopes' => [1], 'tags' => ['process_emissions_unclear']]],
                ],
            ],
        ],

        'finance' => [
            'financed' => [
                'question' => 'Do you lend, invest or underwrite?',
                'help' => 'For a financial institution, emissions from what you finance are normally 95%+ of the total.',
                'options' => [
                    'yes_both' => ['label' => 'Yes — loans and investments', 'implies' => ['categories' => [15], 'tags' => ['financed_emissions', 'pcaf']]],
                    'yes_loans' => ['label' => 'Yes — lending only', 'implies' => ['categories' => [15], 'tags' => ['financed_emissions']]],
                    'no' => ['label' => 'No — advisory or services only', 'implies' => ['exclude_categories' => [15]]],
                ],
            ],
        ],

        'retail' => [
            'refrigeration' => [
                'question' => 'Do you run chilled or frozen display cabinets?',
                'help' => 'Supermarket refrigerant leakage is often larger than the electricity used to run the cabinets.',
                'options' => [
                    'yes' => ['label' => 'Yes', 'implies' => ['scopes' => [1], 'tags' => ['commercial_refrigeration']]],
                    'no' => ['label' => 'No', 'implies' => []],
                ],
            ],
            'franchise' => [
                'question' => 'Do you operate franchises?',
                'help' => 'If you are the franchisor, your franchisees’ emissions belong in your inventory.',
                'options' => [
                    'franchisor' => ['label' => 'Yes — we franchise our brand to others', 'implies' => ['categories' => [14], 'tags' => ['franchisor']]],
                    'franchisee' => ['label' => 'We are a franchisee of someone else', 'implies' => []],
                    'no' => ['label' => 'No franchises', 'implies' => ['exclude_categories' => [14]]],
                ],
            ],
        ],

        'hospitality' => [
            'laundry' => [
                'question' => 'How is your laundry handled?',
                'help' => 'Outsourced laundry moves the emissions into your supply chain rather than your own energy bill.',
                'options' => [
                    'in_house' => ['label' => 'In-house', 'implies' => ['scopes' => [1, 2]]],
                    'outsourced' => ['label' => 'Outsourced to a laundry company', 'implies' => ['categories' => [1], 'tags' => ['outsourced_laundry']]],
                ],
            ],
        ],

        'education' => [
            'transport' => [
                'question' => 'How do students and staff get to your campus?',
                'help' => 'Commuting is usually the biggest single line for a school or university — and the most often left out.',
                'options' => [
                    'own_buses' => ['label' => 'We run our own buses', 'implies' => ['scopes' => [1], 'categories' => [7], 'tags' => ['school_buses']]],
                    'private' => ['label' => 'They travel independently', 'implies' => ['categories' => [7]]],
                    'both' => ['label' => 'Both', 'implies' => ['scopes' => [1], 'categories' => [7], 'tags' => ['school_buses']]],
                ],
            ],
        ],

        'agriculture' => [
            'livestock' => [
                'question' => 'Do you keep livestock?',
                'help' => 'Digestion and manure release methane, which is far more potent than CO₂.',
                'options' => [
                    'yes' => ['label' => 'Yes', 'implies' => ['scopes' => [1], 'tags' => ['enteric_fermentation', 'manure_management']]],
                    'no' => ['label' => 'No — crops only', 'implies' => ['scopes' => [1], 'tags' => ['fertiliser_use']]],
                ],
            ],
        ],

        'mining' => [
            'fugitive' => [
                'question' => 'Does gas escape from your extraction operations?',
                'help' => 'Seam gas and vented methane can exceed all your fuel use combined.',
                'options' => [
                    'yes' => ['label' => 'Yes', 'implies' => ['scopes' => [1], 'tags' => ['fugitive_methane']]],
                    'no' => ['label' => 'No', 'implies' => []],
                    'not_sure' => ['label' => 'Not sure', 'implies' => ['scopes' => [1], 'tags' => ['fugitive_unclear']]],
                ],
            ],
        ],

        'chemical' => [
            'feedstock' => [
                'question' => 'Do you use natural gas or oil as a raw material, not just as fuel?',
                'help' => 'Feedstock use is reported differently from combustion, and mixing them up is a common finding in assurance.',
                'options' => [
                    'yes' => ['label' => 'Yes', 'implies' => ['scopes' => [1], 'tags' => ['feedstock_use', 'process_emissions']]],
                    'no' => ['label' => 'No — fuel only', 'implies' => ['scopes' => [1]]],
                    'not_sure' => ['label' => 'Not sure', 'implies' => ['scopes' => [1], 'tags' => ['feedstock_unclear']]],
                ],
            ],
        ],

        'textile' => [
            'wet_processing' => [
                'question' => 'Who does your dyeing and finishing?',
                'help' => 'Wet processing is the most energy-intensive stage. If a subcontractor does it, it is still in your inventory.',
                'options' => [
                    'in_house' => ['label' => 'We do it ourselves', 'implies' => ['scopes' => [1, 2], 'tags' => ['wet_processing']]],
                    'outsourced' => ['label' => 'A subcontractor does it', 'implies' => ['categories' => [1], 'tags' => ['outsourced_wet_processing']]],
                ],
            ],
        ],

        'energy' => [
            'sold_fuel' => [
                'question' => 'Do you sell fuel, gas or electricity to others?',
                'help' => 'Emissions from the energy your customers burn are part of your inventory too.',
                'options' => [
                    'yes' => ['label' => 'Yes', 'implies' => ['categories' => [11], 'tags' => ['sold_energy_products']]],
                    'no' => ['label' => 'No — we only generate for ourselves', 'implies' => []],
                ],
            ],
        ],

        'technology' => [
            'compute' => [
                'question' => 'Where does your computing run?',
                'help' => 'Cloud compute is a purchased service; your own servers are your own energy.',
                'options' => [
                    'cloud' => ['label' => 'Public cloud (AWS, Azure, Google)', 'implies' => ['categories' => [1], 'tags' => ['cloud_compute']]],
                    'own_dc' => ['label' => 'Our own servers or data centre', 'implies' => ['scopes' => [1, 2], 'tags' => ['owned_data_centre']]],
                    'both' => ['label' => 'Both', 'implies' => ['scopes' => [2], 'categories' => [1], 'tags' => ['cloud_compute']]],
                ],
            ],
        ],

        'food_beverage' => [
            'agriculture_inputs' => [
                'question' => 'Do you buy raw agricultural products?',
                'help' => 'Farming inputs usually dominate a food company’s footprint, well ahead of its own factory.',
                'options' => [
                    'yes' => ['label' => 'Yes — crops, dairy, meat', 'implies' => ['categories' => [1], 'tags' => ['agricultural_inputs']]],
                    'no' => ['label' => 'No — we buy processed ingredients', 'implies' => ['categories' => [1]]],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sub-industry specific
    |--------------------------------------------------------------------------
    */
    'by_sub_industry' => [

        'car_rental' => [
            'rental_duration' => [
                'question' => 'Are your rentals mostly short-term or long-term leases?',
                'help' => 'Both sit in category 13, but long leases usually mean you can get real mileage data from the contract.',
                'options' => [
                    'short' => ['label' => 'Short-term — daily and weekly hire', 'implies' => ['categories' => [13], 'tags' => ['short_term_rental']]],
                    'long' => ['label' => 'Long-term leases', 'implies' => ['categories' => [13], 'tags' => ['long_term_lease', 'mileage_available']]],
                    'both' => ['label' => 'Both', 'implies' => ['categories' => [13], 'tags' => ['short_term_rental', 'long_term_lease']]],
                ],
            ],
            'fuel_policy' => [
                'question' => 'Who pays for the fuel in a rental?',
                'help' => 'If you refuel the vehicles yourself, you already hold the fuel data you need.',
                'options' => [
                    'customer' => ['label' => 'The customer returns it full', 'implies' => ['tags' => ['no_fuel_records', 'estimate_from_mileage']]],
                    'we_refuel' => ['label' => 'We refuel and recharge them', 'implies' => ['tags' => ['fuel_records_available']]],
                    'mixed' => ['label' => 'A mix', 'implies' => ['tags' => ['partial_fuel_records']]],
                ],
            ],
        ],

        'cement' => [
            'clinker' => [
                'question' => 'Do you produce your own clinker, or import it?',
                'help' => 'Making clinker releases CO₂ from the limestone itself — around 60% of cement emissions, separate from the kiln fuel.',
                'options' => [
                    'produce' => ['label' => 'We produce clinker on site', 'implies' => ['scopes' => [1], 'tags' => ['clinker_calcination', 'process_emissions']]],
                    'import' => ['label' => 'We import clinker and grind it', 'implies' => ['categories' => [1], 'tags' => ['imported_clinker']]],
                    'both' => ['label' => 'Both', 'implies' => ['scopes' => [1], 'categories' => [1], 'tags' => ['clinker_calcination']]],
                ],
            ],
        ],

        'data_centre' => [
            'colocation' => [
                'question' => 'Do you host other companies’ equipment?',
                'help' => 'Power drawn by a tenant’s racks is theirs to report and yours to report as downstream leased assets.',
                'options' => [
                    'yes' => ['label' => 'Yes — we provide colocation', 'implies' => ['categories' => [13], 'tags' => ['colocation_provider']]],
                    'no' => ['label' => 'No — all equipment is ours', 'implies' => ['scopes' => [2]]],
                ],
            ],
        ],

        'shipping' => [
            'scope_of_voyage' => [
                'question' => 'Do you operate international voyages?',
                'help' => 'International bunkers are reported separately under most regimes, including EU-MRV.',
                'options' => [
                    'international' => ['label' => 'Yes, international', 'implies' => ['scopes' => [1], 'tags' => ['international_bunkers', 'eu_mrv']]],
                    'domestic' => ['label' => 'Domestic only', 'implies' => ['scopes' => [1]]],
                ],
            ],
        ],
    ],
];
