<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * When null = use role-based sidebar visibility. When array = only show selected routes for this user.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('allowed_sidebar_routes')->nullable()->after('is_demo_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('allowed_sidebar_routes');
        });
    }
};
