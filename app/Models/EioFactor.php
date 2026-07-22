<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class EioFactor extends Model
{
    use HasFactory;

    protected $fillable = [
        'sector_code',
        'sector_name',
        'country',
        'currency',
        'emission_factor',
        'factor_unit',
        'data_source',
        'year',
        'description',
        'is_active',
    ];

    protected $casts = [
        'emission_factor' => 'decimal:6',
        'is_active' => 'boolean',
        'year' => 'integer',
    ];

    /**
     * Get emission factor for a sector and country.
     */
    public static function getFactor($sectorCode, $country = 'USA', $year = null)
    {
        $query = static::where('sector_code', $sectorCode)
            ->where('country', $country)
            ->where('is_active', true);

        if ($year) {
            $query->where('year', $year);
        } else {
            $query->orderBy('year', 'desc');
        }

        return $query->first();
    }

    /**
     * Estimate emissions from a spend amount, returned in tonnes CO₂e (the
     * app's canonical unit). Prefers a factor in the spend's currency, then the
     * country's, then a generic default; normalises the result from the
     * factor's stated unit (kg- vs tonne-denominated). Returns null if no
     * factor is available.
     */
    public static function calculateFromSpend($spendAmount, $sectorCode, $country = 'USA', $currency = 'USD')
    {
        $factor = static::resolveSpendFactor($sectorCode, $country, $currency);

        if (!$factor) {
            return null;
        }

        // The factor is per-unit-of-its-own-currency, so convert the spend into
        // that currency before multiplying — otherwise e.g. an AED spend against
        // a USD factor is treated as USD (overstating by the exchange ratio).
        $spendForCalc = (float) $spendAmount;
        if ($currency && strtoupper((string) $factor->currency) !== strtoupper((string) $currency)) {
            $converted = static::convertCurrency((float) $spendAmount, $currency, $factor->currency);
            if ($converted !== null) {
                $spendForCalc = $converted;
            } else {
                // Unknown rate for one of the currencies — fall back to the raw
                // spend and surface it rather than silently mixing currencies.
                Log::warning('EIO spend estimate: no FX rate to convert spend to factor currency', [
                    'sector_code'     => $sectorCode,
                    'spend_currency'  => $currency,
                    'factor_currency' => $factor->currency,
                ]);
            }
        }

        $raw = $spendForCalc * (float) $factor->emission_factor;

        return round(static::normalizeToTonnes($raw, $factor->factor_unit), 6);
    }

    /**
     * Convert an amount between currencies using config/fx_rates.php (units per
     * 1 USD). Returns null when either currency has no configured rate, so the
     * caller can decide how to handle it rather than getting a wrong number.
     */
    protected static function convertCurrency(float $amount, ?string $from, ?string $to): ?float
    {
        $from = strtoupper((string) $from);
        $to = strtoupper((string) $to);

        if ($from === '' || $to === '' || $from === $to) {
            return $amount;
        }

        $rates = config('fx_rates.rates', []);
        if (empty($rates[$from]) || empty($rates[$to])) {
            return null;
        }

        // amount(from) -> USD -> to
        return $amount / (float) $rates[$from] * (float) $rates[$to];
    }

    /**
     * Resolve the best EIO factor for a spend estimate: prefer the spend's
     * currency, then the country's factor, then any factor for the sector.
     */
    protected static function resolveSpendFactor($sectorCode, $country, $currency)
    {
        if ($currency) {
            $byCurrency = static::active()
                ->where('sector_code', $sectorCode)
                ->where('currency', $currency)
                ->orderBy('year', 'desc')
                ->first();

            if ($byCurrency) {
                return $byCurrency;
            }
        }

        return static::getFactor($sectorCode, $country)
            ?? static::active()
                ->where('sector_code', $sectorCode)
                ->orderBy('year', 'desc')
                ->first();
    }

    /**
     * Convert a raw (spend × factor) result to tonnes CO₂e using the factor's
     * unit. Seeded factors are kg_CO2e_per_USD, so kg-denominated factors are
     * divided by 1000; tonne-denominated factors pass through.
     */
    protected static function normalizeToTonnes(float $value, ?string $factorUnit): float
    {
        $unit = strtolower((string) $factorUnit);

        if (str_contains($unit, 'kg')) {
            return $value / 1000;
        }

        if (str_contains($unit, 'tonne') || str_contains($unit, 't_co2e') || str_contains($unit, 'tco2e')) {
            return $value;
        }

        // Unknown unit: assume kg (the seeded convention) for consistency.
        return $value / 1000;
    }

    /**
     * Scope to active factors.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by country.
     */
    public function scopeForCountry($query, $country)
    {
        return $query->where('country', $country);
    }
}
