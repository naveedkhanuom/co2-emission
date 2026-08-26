<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Our own staff, in the CENTRAL database.
 *
 * Deliberately not the tenant users table. A platform account has to reach
 * every client, and putting it inside one client's database would mean either
 * duplicating it into all of them or granting one client's row authority over
 * the others. Keeping it central means a tenant's users table contains only
 * that client's people — which is also what makes handing over the database
 * a clean act.
 *
 * There is no registration route. Accounts are created with platform:user.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_users');
    }
};
