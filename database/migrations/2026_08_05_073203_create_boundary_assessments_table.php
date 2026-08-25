<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One Boundary Advisor run per company per reporting year, versioned so a
 * re-assessment never overwrites the record an auditor may already have seen.
 * Holds the frozen profile, the clarifying Q&A transcript and the AI
 * provenance (model, prompt version, confidence) required for assurance.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('boundary_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->year('reporting_year');
            $table->enum('status', ['draft', 'active', 'superseded'])->default('draft');
            $table->unsignedInteger('version')->default(1);

            $table->json('profile_snapshot')->nullable()->comment('Answers as given, frozen at generation');
            $table->json('questions')->nullable()->comment('Clarifying question + answer transcript');
            $table->text('summary')->nullable()->comment('Plain-language boundary statement');

            // AI provenance — non-negotiable for audited carbon data.
            $table->string('model')->nullable();
            $table->string('prompt_version')->nullable();
            $table->decimal('confidence', 4, 2)->nullable();
            $table->string('generator', 20)->default('template')->comment('ai | template');
            $table->timestamp('generated_at')->nullable();

            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'reporting_year', 'version']);
            $table->index(['company_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boundary_assessments');
    }
};
