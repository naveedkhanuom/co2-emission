<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * For demo users: which sidebar links to show but restrict (lock + no permission on click).
     * When null = use config('demo.restricted_routes'). When array = only these are restricted for this user.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('restricted_sidebar_routes')->nullable()->after('allowed_sidebar_routes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('restricted_sidebar_routes');
        });
    }
};
