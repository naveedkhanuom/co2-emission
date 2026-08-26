<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Allow EmissionRecords sourced from supplier surveys to be tagged as such,
     * distinct from manual / import / api origins.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE emission_records MODIFY data_source ENUM('manual','import','api','supplier-survey') NOT NULL DEFAULT 'manual'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE emission_records MODIFY data_source ENUM('manual','import','api') NOT NULL DEFAULT 'manual'");
    }
};
