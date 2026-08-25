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
     * @return array<string, mixed>
     */
    public function verify(array $data): array
    {
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
