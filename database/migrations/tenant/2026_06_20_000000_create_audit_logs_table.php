<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Immutable change log for audited models (emission records, factors, targets…).
 * Records who changed what, when — required for ISO 14064-3 / CSRD assurance.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Tenant + actor (nullable: system/console actions have no user).
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_name')->nullable();

            // What happened, and to which model row.
            $table->string('event'); // created | updated | deleted | restored
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');

            // The before/after snapshot of the changed attributes only.
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // Request context.
            $table->string('url')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
