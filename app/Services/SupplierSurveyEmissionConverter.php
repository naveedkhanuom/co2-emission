<?php

namespace App\Services;

use App\Models\EmissionRecord;
use App\Models\ReportingPeriod;
use App\Models\SupplierSurvey;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Converts a completed supplier survey's answers into Scope 3 EmissionRecords.
 *
 * Only questions the company tagged with an emission mapping (a Scope 3 category
 * + an emission factor in tCO₂e per unit) and that received a positive numeric
 * answer become records. Records land as `draft` with `data_quality = primary`
 * so a human reviews supplier-reported data before it counts (the project's
 * human-in-the-loop guardrail). co2e is stored in tonnes, the app's canonical
 * unit (see the co2e-canonical-unit note).
 */
class SupplierSurveyEmissionConverter
{
    /**
     * @return int Number of EmissionRecords created.
     */
    public function convert(SupplierSurvey $survey): int
    {
        // Idempotent: a survey is converted at most once.
        if ($survey->emissions_generated_at) {
            return 0;
        }

        $questions = is_array($survey->questions) ? $survey->questions : [];
        $responses = is_array($survey->responses) ? $survey->responses : [];

        if (empty($questions)) {
            return 0;
        }

        $entryDate = ($survey->completed_at ?? now())->toDateString();

        // A survey whose date falls in a locked (finalised) period produces
        // nothing. The approval step already refuses to activate such a record,
        // so converting would only fill the review queue with drafts nobody can
        // action.
        //
        // emissions_generated_at is deliberately NOT set here: conversion is
        // idempotent on that column, so leaving it null means the survey can be
        // converted later if the period is reopened. Marking it would strand the
        // supplier's answers permanently.
        //
        // This path is reachable from the PUBLIC supplier portal, where the
        // submitter is an unauthenticated third party — so it returns quietly
        // rather than raising something a supplier would see. The buyer's
        // reporting calendar is not the supplier's business.
        if ($survey->company_id && ReportingPeriod::isYearLocked(
            (int) Carbon::parse($entryDate)->year,
            (int) $survey->company_id
        )) {
            Log::info('Supplier survey not converted: reporting period locked', [
                'survey_id' => $survey->id,
                'company_id' => $survey->company_id,
                'year' => (int) Carbon::parse($entryDate)->year,
            ]);

            return 0;
        }

        // facility is a 50-char column; truncate so a long supplier name can't
        // throw mid-conversion and silently skip the record.
        $supplierName = Str::limit($survey->supplier->name ?? 'N/A', 50, '');
        $enricher = app(EmissionEnrichmentService::class);
        $created = 0;

        foreach ($questions as $index => $q) {
            if (! is_array($q) || empty($q['maps_to_emissions'])) {
                continue;
            }

            $categoryId = $q['scope3_category_id'] ?? null;
            $factor = isset($q['emission_factor']) && is_numeric($q['emission_factor'])
                ? (float) $q['emission_factor']
                : null;

            // A usable mapping needs both a category and a factor.
            if (! $categoryId || $factor === null || $factor < 0) {
                continue;
            }

            $answer = $responses[$index] ?? null;
            if (! is_numeric($answer) || (float) $answer <= 0) {
                continue; // no quantity to convert
            }

            $activity = (float) $answer;
            $unit = $q['activity_unit'] ?? null;

            $data = [
                'company_id' => $survey->company_id,
                'supplier_id' => $survey->supplier_id,
                'entry_date' => $entryDate,
                // These are supplier-level Scope 3 emissions; record the supplier
                // as the "facility" so it groups under the supplier in reports.
                'facility' => $supplierName,
                'scope' => 3,
                'scope3_category_id' => $categoryId,
                'emission_source' => Str::limit(is_string($q['question'] ?? null) ? $q['question'] : 'Supplier-reported activity', 100, ''),
                'activity_data' => $activity,
                // The unit was already known here and was being written only into
                // the notes prose. It belongs in the column: without it the figure
                // states a quantity of nothing, and EmissionFigureVerifier cannot
                // re-derive activity × factor.
                'activity_unit' => $unit,
                'emission_factor' => $factor,
                'co2e_value' => round($activity * $factor, 4), // tonnes (canonical)
                'calculation_method' => 'activity-based',
                'data_quality' => 'primary',
                'confidence_level' => 'high',
                'data_source' => 'supplier-survey',
                'notes' => "From supplier survey \"{$survey->title}\" (#{$survey->id}); supplier: {$supplierName}"
                    .($unit ? "; reported unit: {$unit}" : ''),
                'created_by' => $survey->created_by,
                'status' => 'draft',
            ];

            // Enrich as every other entry path does: GWP stamp, factor lock where
            // one can be resolved, per-gas split. Supplier-reported figures are
            // the ones an assurer scrutinises hardest, so they least of all should
            // arrive stating no GWP basis.
            EmissionRecord::create($enricher->enrich($data));

            $created++;
        }

        $survey->forceFill(['emissions_generated_at' => now()])->save();

        Log::info('Supplier survey converted to emission records', [
            'survey_id' => $survey->id,
            'supplier_id' => $survey->supplier_id,
            'records_created' => $created,
        ]);

        return $created;
    }
}
