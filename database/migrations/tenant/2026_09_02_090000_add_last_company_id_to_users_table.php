<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remembers which company a user last worked in, across sessions.
 *
 * The company switcher wrote its choice to `current_company_id` in the SESSION
 * and nowhere else, and LoginController::logout() calls session()->flush() —
 * which is the right thing for a logout to do. So the selection could not
 * survive one.
 *
 * For most users that was invisible: SetCompanyConnection falls back to
 * `users.company_id`, so their own company reappeared. An ACCOUNT OWNER has no
 * `company_id` — that is what makes them an owner rather than a member — so the
 * fallback yielded null, no company was bound, and the sidebar dropped back to
 * "Select Company" on every fresh login.
 *
 * WHY A SEPARATE COLUMN
 *
 * `company_id` means "this user belongs to this company" and drives
 * canAccessCompany() and the query scope. Writing a switcher preference into it
 * would silently grant a member permanent access to whichever company they last
 * looked at, and would turn an account owner into a member of one company.
 * These are two different facts and they need two columns.
 *
 * Nullable and ON DELETE SET NULL: a remembered company that is later deleted
 * must not block the user from signing in, it must simply be forgotten.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('last_company_id')->nullable()->after('company_id');

            $table->foreign('last_company_id')
                ->references('id')->on('companies')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['last_company_id']);
            $table->dropColumn('last_company_id');
        });
    }
};
