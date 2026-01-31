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
        Schema::create('targets', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('site_id')->nullable()->index();

            $table->string('name');
            $table->enum('type', ['sbt', 'net-zero', 'carbon-neutral', 'regulatory', 'internal'])->default('internal');
            // Examples: "1", "2", "3", "1-2", "all"
            $table->string('scope', 10)->default('all');

            $table->unsignedSmallInteger('baseline_year')->nullable();
            $table->decimal('baseline_emissions', 12, 2)->nullable();

            $table->unsignedSmallInteger('target_year');
            $table->decimal('target_emissions', 12, 2)->nullable();
            $table->decimal('reduction_percent', 5, 2)->nullable();

            $table->string('strategy')->nullable();
            $table->enum('review_frequency', ['monthly', 'quarterly', 'biannual', 'annual'])->default('quarterly');
            $table->string('responsible_person')->nullable();

            $table->enum('status', ['on-track', 'at-risk', 'off-track', 'completed'])->default('on-track')->index();
            $table->text('description')->nullable();

            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();

            // NOTE: Foreign keys intentionally omitted (project has multiple migrations that drop tables)
            // You can add FKs later with a dedicated migration when the schema is stable.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('targets');
    }
};


