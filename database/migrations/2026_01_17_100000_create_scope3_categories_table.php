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
        Schema::create('scope3_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // '3.1', '3.2', etc.
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('category_type', ['upstream', 'downstream'])->default('upstream');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index('category_type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scope3_categories');
    }
};
