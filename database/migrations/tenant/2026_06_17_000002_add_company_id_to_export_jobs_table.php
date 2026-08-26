<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Export jobs run outside a request (queue), so they carry their own
     * company_id for tenant-correct data instead of relying on request context.
     */
    public function up(): void
    {
        Schema::table('export_jobs', function (Blueprint $table) {
            $table->unsignedBigInteger('company_id')->nullable()->after('id');
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::table('export_jobs', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
