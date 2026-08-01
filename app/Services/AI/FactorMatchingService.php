<?php

namespace App\Services\AI;

use App\Models\EmissionFactor;
use Illuminate\Support\Str;

/**
 * Phase 2: match an extracted line item against the company's real emission
 * factor library instead of trusting the AI's estimated factor.
 *
 * When the AI has mapped a line to one of the known emission-source names, we
 * look up the authoritative EmissionFactor row — preferring a country-specific
 * factor, then a default-region factor — and return it so the record can be
 * *locked* to that factor (emission_factor_id) on save. This is the audit-safe
 * path: a real, cited factor beats an AI guess.
 *
 * factor_value is stored in tCO2e per unit (the app's canonical basis), so no
 * kg/tonne conversion happens here.
 */
class FactorMatchingService
{
    /**
     * Find the best library factor for a source name + unit, preferring the
     * company's country. Returns null when nothing suitable exists.
     *
     * @return array{
     *   emission_factor_id:int, factor_value:float, unit:string,
     *   region:?string, organization:?string, dataset:?string
     * }|null
     */
    public function match(string $sourceName, ?string $unit, ?string $countryCode): ?array
    {
        $sourceName = trim($sourceName);
        if ($sourceName === '') {
            return null;
        }

        $candidates = EmissionFactor::with(['organization', 'country', 'emissionSource'])
            ->whereHas('emissionSource', fn ($q) => $q->whereRaw('LOWER(name) = ?', [Str::lower($sourceName)]))
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        // Prefer a factor whose unit is compatible with the extracted unit.
        $wantUnit = $this->normalizeUnit($unit);
        if ($wantUnit !== null) {
            $unitMatched = $candidates->filter(
                fn ($f) => $this->normalizeUnit($f->unit) === $wantUnit
            );
            if ($unitMatched->isNotEmpty()) {
                $candidates = $unitMatched;
            }
            // If no unit matches, fall through to all candidates (the source is
            // still a strong signal); the caller keeps the extracted quantity.
        }

        $best = $candidates->sort(function ($a, $b) use ($countryCode) {
            // 1. Active factors first.
            $byActive = ($b->is_active ? 1 : 0) <=> ($a->is_active ? 1 : 0);
            if ($byActive !== 0) return $byActive;

            // 2. Country-specific match beats a generic/default one.
            $aCountry = $this->isCountryMatch($a, $countryCode) ? 1 : 0;
            $bCountry = $this->isCountryMatch($b, $countryCode) ? 1 : 0;
            if ($aCountry !== $bCountry) return $bCountry <=> $aCountry;

            // 3. Newest row wins.
            return $b->id <=> $a->id;
        })->first();

        if (!$best) {
            return null;
        }

        return [
            'emission_factor_id' => $best->id,
            'factor_value'       => (float) $best->factor_value,   // tCO2e per unit
            'unit'               => $best->unit,
            'region'             => $best->country?->code ?? $best->region,
            'organization'       => $best->organization?->code ?? $best->organization?->name,
            'dataset'            => $best->datasetLabel(),
        ];
    }

    protected function isCountryMatch(EmissionFactor $f, ?string $countryCode): bool
    {
        if (!$countryCode || $countryCode === 'default') {
            return false;
        }
        $code = $f->country?->code ?? $f->region;
        return $code !== null && Str::lower($code) === Str::lower($countryCode);
    }

    /** Loose unit normalisation so "L"/"litre"/"liters" compare equal. */
    protected function normalizeUnit(?string $unit): ?string
    {
        $u = Str::lower(trim((string) $unit));
        if ($u === '') {
            return null;
        }
        $map = [
            'l' => 'liters', 'litre' => 'liters', 'litres' => 'liters', 'liter' => 'liters', 'liters' => 'liters',
            'kwh' => 'kwh',
            'mwh' => 'mwh',
            'm3' => 'm3', 'm³' => 'm3', 'cubic metre' => 'm3', 'cubic meter' => 'm3',
            'km' => 'km', 'kilometre' => 'km', 'kilometer' => 'km',
            'kg' => 'kg', 'kgs' => 'kg', 'kilogram' => 'kg', 'kilograms' => 'kg',
            't' => 'tonne', 'tonne' => 'tonne', 'tonnes' => 'tonne', 'ton' => 'tonne',
        ];
        return $map[$u] ?? $u;
    }
}
