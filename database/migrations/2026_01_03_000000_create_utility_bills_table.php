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
        // Drop table if it exists (in case of previous failed migration)
        Schema::dropIfExists('utility_bills');
        
        Schema::create('utility_bills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('site_id')->nullable();
            $table->string('file_path');
            $table->string('bill_type')->default('electricity'); // electricity, fuel, gas
            $table->string('supplier_name')->nullable();
            $table->date('bill_date')->nullable();
            $table->decimal('consumption', 12, 2)->nullable(); // kWh for electricity, liters/gallons for fuel
            $table->string('consumption_unit')->nullable(); // kWh, L, gal
            $table->decimal('cost', 12, 2)->nullable();
            $table->text('raw_text')->nullable(); // OCR extracted text
            $table->text('raw_response')->nullable(); // Full API response
            $table->json('extracted_data')->nullable(); // Parsed structured data
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('emission_record_id')->nullable(); // Link to created emission record
            $table->timestamps();

            // Note: Foreign keys removed to avoid constraint issues
            // Relationships will still work in Laravel Eloquent models
            // You can add foreign keys later if needed using a separate migration
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('utility_bills');
    }
};

