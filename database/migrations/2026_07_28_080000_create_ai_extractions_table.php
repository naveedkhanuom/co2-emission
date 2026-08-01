<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit trail for AI document extraction (Phase 3). Records every upload: what
 * file was processed, what the AI proposed, and how many line items were saved
 * as draft emission records — so an auditor can see the provenance of any
 * AI-assisted entry and compare "proposed vs saved".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_extractions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('file_name');
            $table->string('file_path')->nullable();
            $table->string('document_type')->nullable();
            $table->enum('status', ['extracted', 'saved', 'discarded', 'failed'])->default('extracted');
            $table->unsignedInteger('items_count')->default(0);
            $table->unsignedInteger('saved_count')->default(0);
            $table->string('currency', 3)->nullable();
            $table->string('prompt_version')->nullable();
            // Full normalised proposal (line items + any created record ids), for audit.
            $table->json('result')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_extractions');
    }
};
