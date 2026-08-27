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
        /** Which catalogue answered: "Scope 1" or "Scope 2". */
        public string $catalogue = 'Scope 1',
        /**
         * True when the underlying factor came from the user rather than the
         * catalogue — a custom grid region, or the Scope 2 EF override.
         *
         * The figure is still verified: checking co2e against activity × this
         * factor still catches arithmetic errors and stale client bundles. What
         * it cannot do is vouch for the factor itself, so the provenance has to
         * say so plainly.
         */
        public bool $userSupplied = false,
        /**
         * Set when the value came from the tenant's own factor library rather
         * than the built-in config catalogue. Scope 3 is resolved that way:
         * its factors are seeded reference data, not a config file.
         */
        public ?int $emissionFactorId = null,
    ) {}

    /**
     * Provenance string for EmissionRecord::factor_dataset.
     *
     * Enrichment overwrites this when it can lock a real EmissionFactor row —
     * a database row is stronger provenance than a config file.
     */
    public function datasetLabel(): string
    {
        if ($this->emissionFactorId !== null) {
            $label = sprintf('Factor library #%d', $this->emissionFactorId);

            if ($this->reference) {
                $label .= ' — '.$this->reference;
            }

            return trim($label);
        }

        $label = sprintf('Built-in %s catalogue %s', $this->catalogue, $this->catalogueVersion);

        if ($this->reference) {
            $label .= ' — '.$this->reference;
        }

        if ($this->userSupplied) {
            $label .= ' (user-supplied factor)';
        }

        return trim($label);
    }
}
