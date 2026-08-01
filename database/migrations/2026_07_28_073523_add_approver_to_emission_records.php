<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Governance: three-step workflow (preparer -> reviewer -> approver). Adds a
 * 'reviewed' intermediate state to the status enum and approver columns, so a
 * record flows draft -> reviewed (reviewed_by) -> active/approved (approved_by).
 * 'active' remains the final, reported state, so existing report queries are
 * unaffected.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE emission_records MODIFY status ENUM('active','draft','reviewed') NOT NULL DEFAULT 'active'");

        Schema::table('emission_records', function (Blueprint $table) {
            $table->unsignedBigInteger('approved_by')->nullable()->after('review_note');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('emission_records', function (Blueprint $table) {
            $table->dropColumn(['approved_by', 'approved_at']);
        });

        // Collapse any 'reviewed' rows back to draft before removing the state.
        DB::table('emission_records')->where('status', 'reviewed')->update(['status' => 'draft']);
        DB::statement("ALTER TABLE emission_records MODIFY status ENUM('active','draft') NOT NULL DEFAULT 'active'");
    }
};
