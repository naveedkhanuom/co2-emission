<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Locking a year declares an inventory final. Under the GHG Protocol that
 * claim depends on a decided boundary — you cannot say a footprint is complete
 * without having said what it was supposed to contain, and the Scope 3
 * Standard requires all 15 categories to be screened for relevance even when
 * most are excluded.
 *
 * ReportingPeriodController::lock() now checks that before freezing a year.
 * It does not refuse outright: five clients are already live with data and no
 * boundary assessment, and taking a governance action away from a paying
 * account is a worse outcome than letting it proceed on the record. Instead
 * the lock can go ahead with a written acknowledgement, which is itself the
 * artefact an assurer wants — the same shape as boundary_items.exclusion_
 * reason, where an exclusion is acceptable precisely because a reason was
 * recorded against it.
 *
 * Two columns rather than reuse of `note`:
 *
 *   - `note` is ordinary user prose, already shown in the UI, and an assurer
 *     asking "which years were signed off without a complete boundary?" cannot
 *     answer that with a LIKE over free text.
 *   - the gap is stored SEPARATELY from the reason because one is the
 *     machine's finding and the other is the human's answer to it. Keeping the
 *     finding verbatim means a later reader can see what was actually missing
 *     at lock time, not what the person locking it believed was missing.
 *
 * Both are null on a period locked with a complete boundary, which is the
 * state to aim for: a null here means the lock needed no excuse.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reporting_periods', function (Blueprint $table) {
            // What the human wrote to justify locking anyway.
            $table->text('boundary_ack_reason')->nullable()->after('note');

            // What the platform found missing at the moment of locking.
            $table->string('boundary_ack_gap', 255)->nullable()->after('boundary_ack_reason');
        });
    }

    public function down(): void
    {
        Schema::table('reporting_periods', function (Blueprint $table) {
            $table->dropColumn(['boundary_ack_reason', 'boundary_ack_gap']);
        });
    }
};
