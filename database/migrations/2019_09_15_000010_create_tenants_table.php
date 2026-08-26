<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTenantsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            // The id is a slug, not a UUID: it becomes the tenant's database
            // name (tenant_acme), and a database you may one day hand to a
            // client should be named after them.
            $table->string('id')->primary();

            $table->string('name');

            // provisioning -> active -> suspended | past_due -> archived,
            // plus failed for a provisioning run that rolled back. Only
            // 'active' serves requests; see App\Models\Tenant.
            $table->string('status', 20)->default('provisioning')->index();

            $table->string('plan', 50)->nullable();

            // Which tenant migration batch this database is on, so "who is
            // behind?" is answerable without connecting to every database.
            $table->string('schema_version', 40)->nullable();

            $table->timestamps();
            $table->json('data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
}
