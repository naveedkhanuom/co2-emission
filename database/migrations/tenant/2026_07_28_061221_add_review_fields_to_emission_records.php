<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Governance: capture who reviewed/validated each record and when, so the
 * inventory has an audit trail and segregation of duties can be surfaced
 * (a preparer approving their own data). created_by already records the
 * preparer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emission_records', function (Blueprint $table) {
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('created_by');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('review_note')->nullable()->after('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('emission_records', function (Blueprint $table) {
            $table->dropColumn(['reviewed_by', 'reviewed_at', 'review_note']);
        });
    }
};
