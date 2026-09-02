<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Records WHICH UNIT `factor_value` is expressed in.
 *
 * Until now the column had no single meaning, and the two importers disagreed:
 *
 *   Diesel, litres — built-in catalogue   0.00268   (tCO2e per litre)
 *   Diesel, litres — DEFRA/DESNZ 2026     2.66155   (kgCO2e per litre)
 *
 * Same fuel, same unit, same column, a factor of 1000 apart. Both are correct
 * on their own terms: BuiltInFactorCatalog divides by 1000 on the way out
 * (see its class docblock), while DefraFlatFileImporter deliberately stores the
 * publisher's own number so a row can still be checked against the published
 * file. Neither wrote down which it had chosen.
 *
 * ResolvedFactor::$value is documented "tCO2e per one unit of activity", and
 * LibraryFactorCatalog passed factor_value straight into it. A DEFRA-sourced
 * factor therefore priced activity 1000x too high, and EmissionFigureVerifier
 * confirmed the result because activity x factor is arithmetically true
 * whatever the units are.
 *
 * WHY A COLUMN RATHER THAN CONVERTING ON IMPORT
 *
 * Converting DEFRA to tonnes on the way in would make the stored number
 * uncheckable against the publisher's spreadsheet, which is the one property
 * database/factors/sources/MANIFEST.md exists to guarantee. Recording the basis
 * keeps the published figure intact and moves the conversion to the point of
 * use, which is where the caller already knows what it wants.
 *
 * BACKFILL
 *
 * By dataset, because that is what actually determined the basis:
 *   - DEFRA/DESNZ            -> kgCO2e  (publisher's own units, as imported)
 *   - everything else        -> tCO2e   (compiled through BuiltInFactorCatalog,
 *                                        which divides by 1000)
 *
 * The 271 legacy rows with no dataset_name are tCO2e: verified against the
 * compiled rows for the same fuels, which agree to three significant figures
 * (Coal kg: legacy 0.00242 vs compiled 0.0024311).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emission_factors', function (Blueprint $table) {
            $table->string('factor_unit', 16)->nullable()->after('factor_value');
        });

        // Publisher-basis datasets. Listed explicitly rather than "anything not
        // built-in": a dataset nobody has classified should surface as NULL and
        // be treated as the legacy default, not silently assumed to be kg.
        DB::table('emission_factors')
            ->whereIn('dataset_name', ['DEFRA/DESNZ', 'EPA'])
            ->update(['factor_unit' => 'kgCO2e']);

        DB::table('emission_factors')
            ->whereNull('factor_unit')
            ->update(['factor_unit' => 'tCO2e']);
    }

    public function down(): void
    {
        Schema::table('emission_factors', function (Blueprint $table) {
            $table->dropColumn('factor_unit');
        });
    }
};
