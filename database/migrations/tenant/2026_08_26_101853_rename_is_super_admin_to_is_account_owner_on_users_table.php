<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * is_super_admin meant platform-wide access when there was one database. Now
 * that each client account has its own, the flag can only ever mean "sees
 * every company in THIS account" — the database boundary is what stops it
 * meaning anything wider.
 *
 * The old name invites exactly the wrong assumption in a security-sensitive
 * spot: HasCompanyScope lets this flag skip the company filter entirely, and
 * a reviewer who reads "super admin" there has to stop and work out whether
 * that crosses clients. It cannot. The name should say so.
 *
 * Platform staff — our people, not a client's — belong in the central
 * database and never appear in a tenant's users table at all.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_super_admin') && ! Schema::hasColumn('users', 'is_account_owner')) {
                $table->renameColumn('is_super_admin', 'is_account_owner');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_account_owner') && ! Schema::hasColumn('users', 'is_super_admin')) {
                $table->renameColumn('is_account_owner', 'is_super_admin');
            }
        });
    }
};
