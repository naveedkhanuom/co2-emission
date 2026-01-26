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
        Schema::table('supplier_surveys', function (Blueprint $table) {
            $table->string('public_token', 64)->nullable()->unique()->after('reminder_count');
            $table->timestamp('public_token_expires_at')->nullable()->after('public_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_surveys', function (Blueprint $table) {
            $table->dropUnique(['public_token']);
            $table->dropColumn(['public_token', 'public_token_expires_at']);
        });
    }
};

