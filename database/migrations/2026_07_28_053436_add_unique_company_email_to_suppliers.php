<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prevent duplicate suppliers within a company (re-importing a supplier list
 * would otherwise create duplicates and double-count Scope 3 spend). MySQL
 * allows multiple NULLs in a unique index, so suppliers without an email are
 * not blocked.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->unique(['company_id', 'email'], 'suppliers_company_email_unique');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropUnique('suppliers_company_email_unique');
        });
    }
};
