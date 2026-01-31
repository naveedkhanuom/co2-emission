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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('industry')->nullable();
            $table->enum('data_quality', ['primary', 'secondary', 'estimated'])->default('estimated');
            $table->json('emission_factors')->nullable(); // Custom factors per supplier
            $table->enum('status', ['active', 'inactive', 'pending'])->default('pending');
            $table->timestamp('last_data_submission')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('company_id');
            $table->index('status');
            $table->index('data_quality');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
