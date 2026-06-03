<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->index(['company_id', 'generated_at']);
        });

        // Backfill existing rows from the creating user's company so the new
        // company scope does not hide historical reports.
        DB::statement('
            UPDATE reports r
            JOIN users u ON r.created_by = u.id
            SET r.company_id = u.company_id
            WHERE r.company_id IS NULL AND u.company_id IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropIndex(['company_id', 'generated_at']);
            $table->dropColumn('company_id');
        });
    }
};
