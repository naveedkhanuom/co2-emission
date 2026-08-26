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
        Schema::create('emission_records', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('entry_date');
            $table->string('facility', 50);
            $table->tinyInteger('scope')->comment('1, 2, or 3');
            $table->string('emission_source', 100);
            $table->decimal('activity_data', 10)->nullable();
            $table->decimal('emission_factor', 10, 4)->nullable();
            $table->decimal('co2e_value', 12);
            $table->enum('confidence_level', ['low', 'medium', 'high']);
            $table->string('department', 100)->nullable();
            $table->enum('data_source', ['manual', 'import', 'api'])->default('manual');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index('fk_emission_records_user');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
            $table->enum('status', ['active', 'draft'])->default('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emission_records');
    }
};
