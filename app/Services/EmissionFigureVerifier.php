<?php

namespace App\Services;

/**
 * Verifies that a record's CO2e figure matches its own activity data and
 * emission factor.
 *
 * The activity-based figure is calculated in the browser and posted to the
 * server, and the Excel import reads it straight from a spreadsheet cell —
 * so until this ran, `co2e_value` was whatever the client said it was. Every
 * downstream artefact (disclosures, GHG Protocol reports, the EAD workbook,
 * analytics, boundary coverage) is derived from that one number, and a wrong
 * value propagates consistently through all of them, which makes it invisible.
 *
 * Policy: the server's own arithmetic wins. A figure that differs materially
 * from `activity_data × emission_factor` is corrected AND the record is held
 * back as a draft with an explanatory note, so it lands in the existing review
 * queue instead of reaching a report unseen. Rejecting outright would break
 * legitimate edge cases; silently overwriting would show the user a different
 * number from the one on screen with no explanation.
 */
class EmissionFigureVerifier
{
    /**
     * Relative difference tolerated before a record is held for review.
     * Loose enough to absorb float and display rounding, tight enough to catch
     * a wrong unit or a stale client bundle.
     */
    public const TOLERANCE = 0.005; // 0.5%

    /**
     * Apply the check to a record's attributes, returning the corrected set.
     *
     * @param  array<string, mixed>  $data
     * @param  string|null  $factorUnit  The activity unit the resolved factor is
     *                                   priced PER, when one was resolved. Supplied
     *                                   so the arithmetic check can be preceded by
     *                                   a dimension check — see verifyDimensions().
     * @return array<string, mixed>
     */
    public function verify(array $data, ?string $factorUnit = null): array
    {
        // Dimensions before arithmetic. A factor priced per mile against an
        // activity measured in litres multiplies out perfectly well, so the
        // check below would confirm it — which is worse than not checking,
        // because the record then carries a verified figure and a locked
        // factor id while being dimensionally meaningless.
        $data = $this->verifyDimensions($data, $factorUnit);

        $expected = $this->expectedCo2e($data);

        if ($expected === null) {
            return $data;
        }

        $posted = $data['co2e_value'] ?? null;

        // Nothing posted: fill it in. Helpful rather than punitive — there is
        // no discrepancy to review.
        if ($posted === null || $posted === '') {
            $data['co2e_value'] = round($expected, 4);

            return $data;
        }

        $posted = (float) $posted;

        if (! $this->isMaterialMismatch($posted, $expected)) {
            return $data;
        }

        $data['co2e_value'] = round($expected, 4);
        $data['status'] = 'draft';
        $data['notes'] = $this->appendNote(
            $data['notes'] ?? null,
            $this->discrepancyNote($posted, $expected)
        );

        return $data;
    }

    /**
     * Hold a record whose activity unit and factor unit do not agree.
     *
     * The arithmetic check cannot see this class of error: `activity × factor`
     * is a true statement about two numbers no matter what they measure. A
     * real record in the live data reached `status = active` with 23.789
     * LITRES priced by a factor published per MILE, verified and locked to a
     * factor id, because every check it passed was a check on the arithmetic.
     *
     * Held for review rather than rejected, matching how a material arithmetic
     * mismatch is already handled: the entry is probably salvageable by picking
     * the right unit, and a hard rejection at save time would lose whatever
     * else the user had typed. The figure itself is left ALONE — unlike an
     * arithmetic mismatch there is no correct value to substitute, since the
     * right factor is a different row entirely.
     *
     * Only runs when a factor was actually resolved. An unresolved factor means
     * there is no second unit to disagree with, and those records are already
     * visibly unverified.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function verifyDimensions(array $data, ?string $factorUnit): array
    {
        $activityUnit = $data['activity_unit'] ?? null;

        if (blank($factorUnit) || blank($activityUnit)) {
            return $data;
        }

        if ($this->sameUnit((string) $activityUnit, (string) $factorUnit)) {
            return $data;
        }

        $data['status'] = 'draft';
        $data['notes'] = $this->appendNote(
            $data['notes'] ?? null,
            sprintf(
                'Held for review: the activity is recorded in %s but the emission factor '
                .'is published per %s. These do not measure the same thing, so the figure '
                .'cannot be checked. Pick a factor priced per %s, or re-enter the activity in %s.',
                $activityUnit,
                $factorUnit,
                $activityUnit,
                $factorUnit
            )
        );

        return $data;
    }

    /**
     * Whether two unit labels name the same unit.
     *
     * Compared loosely on purpose. The catalogues spell the same unit several
     * ways — "litres"/"liters"/"litre", "tonnes"/"tonne" — because each keeps
     * its publisher's spelling, and a record carries whichever the entry form
     * offered. Treating those as a mismatch would hold correct records for
     * review, which trains people to click past the warning.
     */
    protected function sameUnit(string $activityUnit, string $factorUnit): bool
    {
        return $this->normaliseUnit($activityUnit) === $this->normaliseUnit($factorUnit);
    }

    /**
     * Fold a unit label to a comparable key: lowercased, stripped of spacing
     * and punctuation, de-pluralised, and with the handful of genuine spelling
     * variants mapped together.
     */
    protected function normaliseUnit(string $unit): string
    {
        $lower = mb_strtolower(trim($unit));

        // Superscripts BEFORE punctuation is stripped. The catalogues carry both
        // "m³" and "m3" for cubic metres; stripping first reduces the former to
        // a bare "m" and would report a mismatch between a unit and itself.
        $lower = strtr($lower, ['²' => '2', '³' => '3']);

        $key = preg_replace('/[^a-z0-9]/', '', $lower) ?? '';

        // British/American spellings of the same unit. Not conversions — these
        // are the SAME quantity written differently, and nothing here may map
        // two units that actually differ.
        $key = strtr($key, [
            'litres' => 'litre',
            'liters' => 'litre',
            'liter' => 'litre',
            'metres' => 'metre',
            'meters' => 'metre',
            'meter' => 'metre',
        ]);

        // Trailing plural, after the variants above so "litres" is not first
        // reduced to "litre" by a different route.
        if (mb_strlen($key) > 2 && str_ends_with($key, 's')) {
            $key = mb_substr($key, 0, -1);
        }

        return $key;
    }

    /**
     * What the figure should be, or null when the record cannot be checked.
     *
     * Unverifiable — and deliberately left alone:
     *  - spend-based records, where EioFactor already computes server-side and
     *    is authoritative;
     *  - records entered as a total CO2e with no activity data or factor, which
     *    is a legitimate entry path.
     */
    public function expectedCo2e(array $data): ?float
    {
        if (($data['calculation_method'] ?? null) === 'spend-based') {
            return null;
        }

        $activity = $data['activity_data'] ?? null;
        $factor = $data['emission_factor'] ?? null;

        if ($activity === null || $activity === '' || $factor === null || $factor === '') {
            return null;
        }

        if (! is_numeric($activity) || ! is_numeric($factor)) {
            return null;
        }

        return (float) $activity * (float) $factor;
    }

    /**
     * Whether the posted figure differs from the expected one by more than the
     * tolerance. Compared relatively, except around zero where a relative
     * comparison is meaningless.
     */
    public function isMaterialMismatch(float $posted, float $expected): bool
    {
        $difference = abs($posted - $expected);

        if (abs($expected) < 1e-9) {
            return $difference > 1e-6;
        }

        return ($difference / abs($expected)) > self::TOLERANCE;
    }

    /**
     * Plain-language explanation for the reviewer — it has to be actionable by
     * someone who is not a carbon accountant.
     */
    protected function discrepancyNote(float $posted, float $expected): string
    {
        return sprintf(
            'Held for review: the submitted figure (%s tCO₂e) did not match activity × factor (%s tCO₂e). '
            .'The calculated figure has been used. Check the activity data, the unit and the emission factor.',
            rtrim(rtrim(number_format($posted, 4, '.', ''), '0'), '.'),
            rtrim(rtrim(number_format($expected, 4, '.', ''), '0'), '.')
        );
    }

    protected function appendNote(?string $existing, string $note): string
    {
        $existing = trim((string) $existing);

        return $existing === '' ? $note : $existing."\n\n".$note;
    }
}
