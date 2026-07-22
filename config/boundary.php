<?php

/**
 * Organizational boundary / consolidation approach (GHG Protocol Corporate
 * Standard, Chapter 3). A company reports emissions from operations it either
 * controls or has an ownership stake in; the chosen approach determines which.
 *
 * Stored per-company as the `consolidation_approach` setting (see the
 * onboarding wizard) and read back by DisclosureReportService when labelling
 * the inventory boundary. Keys are stable machine values; labels are shown to
 * users and printed on disclosures.
 */
return [
    // Used when a company has not chosen one (GHG Protocol's most common default).
    'default' => 'operational_control',

    'labels' => [
        'operational_control' => 'Operational control',
        'financial_control'   => 'Financial control',
        'equity_share'        => 'Equity share',
    ],

    // Plain-language helper text for the setup wizard (non-experts).
    'help' => [
        'operational_control' => 'Count 100% of emissions from operations you run day-to-day. Most companies pick this.',
        'financial_control'   => 'Count 100% of emissions from operations you control financially.',
        'equity_share'        => 'Count emissions in proportion to your ownership share of each operation.',
    ],
];
