<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // Drop old foreign keys and columns
            $table->dropForeign(['company_id']);
            $table->dropForeign(['site_id']);
            $table->dropColumn(['company_id', 'site_id']);
            
            // Add new columns
            $table->unsignedInteger('facility_id')->after('id');
            $table->unsignedInteger('department_id')->nullable()->after('facility_id');
            
            // Add new foreign keys
            if (Schema::hasTable('facilities')) {
                $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
            }
            if (Schema::hasTable('departments')) {
                $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            }
            
            // Update indexes
            $table->index('facility_id');
            $table->index('department_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // Drop new foreign keys and columns
            $table->dropForeign(['facility_id']);
            $table->dropForeign(['department_id']);
            $table->dropColumn(['facility_id', 'department_id']);
            
            // Restore old columns
            $table->unsignedBigInteger('company_id')->after('id');
            $table->unsignedBigInteger('site_id')->nullable()->after('company_id');
            
            // Restore old foreign keys
            if (Schema::hasTable('companies')) {
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            }
            if (Schema::hasTable('sites')) {
                $table->foreign('site_id')->references('id')->on('sites')->onDelete('set null');
            }
            
            $table->index('company_id');
        });
    }
};
