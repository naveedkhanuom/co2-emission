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
            if (!Schema::hasColumn('reports', 'views_count')) {
                $table->unsignedInteger('views_count')->default(0)->after('type');
            }
            if (!Schema::hasColumn('reports', 'last_viewed_at')) {
                $table->timestamp('last_viewed_at')->nullable()->after('views_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (Schema::hasColumn('reports', 'views_count')) {
                $table->dropColumn('views_count');
            }
            if (Schema::hasColumn('reports', 'last_viewed_at')) {
                $table->dropColumn('last_viewed_at');
            }
        });
    }
};

