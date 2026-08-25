<?php

namespace App\Services\Factors;

/**
 * A factor the server derived for itself, with the provenance needed to defend
 * the number later.
 */
readonly class ResolvedFactor
{
    public function __construct(
        /** tCO2e per one unit of activity. */
        public float $value,
        /** Canonical unit key the value is per, e.g. "liters", "kg", "kWh". */
        public string $unit,
        /** Catalogue source name the value came from. */
        public string $source,
        /** The catalogue's own citation, e.g. "IPCC 98300 kgCO2/TJ, NCV 26.5 GJ/t". */
        public ?string $reference,
        /** GWP set the value was computed under, e.g. "ar5". */
        public string $gwpVersion,
        /** Catalogue revision, so a stored figure stays traceable. */
        public string $catalogueVersion,
    ) {}

    /**
     * Provenance string for EmissionRecord::factor_dataset.
     *
     * Enrichment overwrites this when it can lock a real EmissionFactor row —
     * a database row is stronger provenance than a config file.
     */
    public function datasetLabel(): string
    {
        return trim(sprintf(
            'Built-in Scope 1 catalogue %s%s',
            $this->catalogueVersion,
            $this->reference ? ' — '.$this->reference : ''
        ));
    }
}
