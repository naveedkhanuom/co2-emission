<?php

namespace App\Support;

use App\Models\CompanySetting;

/**
 * Resolves Global Warming Potential sets (config/gwp.php) and converts masses of
 * individual greenhouse gases into CO2-equivalent. Centralised so every entry
 * path and disclosure report agrees on the same numbers and labels.
 */
class Gwp
{
    /** Valid assessment-report keys, newest first. */
    public const VERSIONS = ['ar6', 'ar5', 'ar4'];

    /**
     * The GWP version a company reports under. Falls back to the global config
     * default ('ar6') when the company has not chosen one.
     */
    public static function versionForCompany(?int $companyId): string
    {
        $default = config('gwp.default', 'ar6');

        if (!$companyId) {
            return $default;
        }

        $value = CompanySetting::where('company_id', $companyId)
            ->where('key', 'gwp_version')
            ->value('value');

        return self::isValid($value) ? $value : $default;
    }

    public static function isValid(?string $version): bool
    {
        return $version !== null && array_key_exists($version, config('gwp.sets', []));
    }

    public static function normalize(?string $version): string
    {
        return self::isValid($version) ? $version : config('gwp.default', 'ar6');
    }

    /** Human label, e.g. "IPCC AR6 (2021)". */
    public static function label(?string $version): string
    {
        $version = self::normalize($version);
        return config("gwp.labels.$version", strtoupper($version));
    }

    /** GWP-100 value for one gas under a given assessment report. */
    public static function factor(string $gas, ?string $version = null): float
    {
        $version = self::normalize($version);
        return (float) (config("gwp.sets.$version.$gas") ?? 0);
    }

    /**
     * Convert a map of gas => kg-mass into total kgCO2e under a GWP set.
     * Unknown gas keys are ignored.
     *
     * @param array<string,float> $masses
     */
    public static function toCo2e(array $masses, ?string $version = null): float
    {
        $version = self::normalize($version);
        $total = 0.0;
        foreach ($masses as $gas => $mass) {
            $total += (float) $mass * self::factor($gas, $version);
        }
        return $total;
    }

    /** Options for select inputs: ['ar6' => 'IPCC AR6 (2021)', ...]. */
    public static function options(): array
    {
        $out = [];
        foreach (self::VERSIONS as $v) {
            $out[$v] = self::label($v);
        }
        return $out;
    }
}
