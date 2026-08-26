<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The boundary checklist itself — one row per candidate parameter the company
 * may need to measure. Each row is decided individually (included / excluded
 * with a written reason / deferred), which is what makes the assessment an
 * audit artefact rather than a suggestion list, and what the coverage metric
 * counts against.
 *
 * `emission_source_id` references the GLOBAL emission_sources catalogue when a
 * match exists — boundary items are the company-scoped layer, never per-tenant
 * EmissionSource rows.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('boundary_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('boundary_assessment_id')->constrained()->cascadeOnDelete();

            $table->tinyInteger('scope')->comment('1, 2, or 3');
            $table->foreignId('scope3_category_id')->nullable()->constrained('scope3_categories')->nullOnDelete();
            $table->foreignId('emission_source_id')->nullable()->constrained('emission_sources')->nullOnDelete();

            $table->string('suggested_name');
            $table->string('suggested_unit', 20)->nullable();

            $table->enum('materiality', ['high', 'medium', 'low'])->default('medium');
            $table->enum('relevance', ['relevant', 'not_relevant', 'unknown'])->default('relevant');
            $table->enum('decision', ['pending', 'included', 'excluded', 'deferred'])->default('pending');
            $table->text('exclusion_reason')->nullable()->comment('Required when decision = excluded');

            $table->text('rationale')->nullable()->comment('Why this applies to THIS company');
            $table->text('data_hint')->nullable()->comment('Where the number lives');
            $table->string('action_type', 30)->nullable()->comment('bill | supplier | import | spend | manual');
            $table->decimal('typical_share_pct', 5, 2)->nullable();

            $table->decimal('confidence', 4, 2)->default(0.5);
            $table->enum('source', ['ai', 'template', 'manual'])->default('template');

            $table->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'decision']);
            $table->index(['boundary_assessment_id', 'scope']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boundary_items');
    }
};
