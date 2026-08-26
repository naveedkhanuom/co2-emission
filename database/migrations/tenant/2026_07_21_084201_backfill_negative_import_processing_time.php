<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Legacy import_history rows recorded processing_time from a signed Carbon 3
 * diff (now()->diffInSeconds($start)), so they were stored negative. Elapsed
 * time is always >= 0 — correct the sign for existing rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('import_history')
            ->where('processing_time', '<', 0)
            ->update(['processing_time' => DB::raw('ABS(processing_time)')]);
    }

    public function down(): void
    {
        // No-op: the original negative values were incorrect; don't restore them.
    }
};
