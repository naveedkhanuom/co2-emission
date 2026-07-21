<?php

/**
 * Approximate foreign-exchange rates, expressed as units of each currency per
 * 1 USD (base = USD). Used only to convert a spend amount into the currency of
 * the EIO spend-based emission factor before multiplying, so an AED spend is
 * not treated as if it were USD.
 *
 * Spend-based (EEIO) estimates are inherently approximate, so a static table is
 * acceptable here and far better than mixing currencies 1:1. Pegged currencies
 * (AED, SAR, QAR, ...) are effectively fixed; floating rates are mid-2020s
 * approximations — refine as needed or wire a live source later.
 */
return [
    'base' => 'USD',

    // 1 USD = <value> of the currency.
    'rates' => [
        'USD' => 1.0,
        'AED' => 3.6725,   // pegged
        'SAR' => 3.75,     // pegged
        'QAR' => 3.64,     // pegged
        'OMR' => 0.3845,   // pegged
        'BHD' => 0.376,    // pegged
        'KWD' => 0.307,
        'EGP' => 48.0,
        'JOD' => 0.709,
        'EUR' => 0.92,
        'GBP' => 0.79,
        'INR' => 83.0,
        'CNY' => 7.2,
        'JPY' => 150.0,
        'AUD' => 1.52,
        'CAD' => 1.36,
        'BRL' => 5.4,
        'ZAR' => 18.5,
    ],
];
