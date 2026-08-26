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
        Schema::table('import_history', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->index(['company_id', 'created_at']);
        });

        // Backfill existing rows from the importing user's company so the new
        // company scope does not hide historical imports.
        DB::statement('
            UPDATE import_history ih
            JOIN users u ON ih.user_id = u.id
            SET ih.company_id = u.company_id
            WHERE ih.company_id IS NULL AND u.company_id IS NOT NULL
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('import_history', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropIndex(['company_id', 'created_at']);
            $table->dropColumn('company_id');
        });
    }
};
