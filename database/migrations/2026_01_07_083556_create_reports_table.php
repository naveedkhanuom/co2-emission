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
        Schema::dropIfExists('reports');
        
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('site_id')->nullable();
            $table->string('report_name');
            $table->string('period', 50);
            $table->date('generated_at');
            $table->enum('status', ['published', 'draft', 'scheduled', 'archived'])->default('draft');
            $table->enum('type', ['executive', 'regulatory', 'internal', 'public'])->default('internal');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // Foreign keys
            if (Schema::hasTable('companies')) {
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            }
            if (Schema::hasTable('sites')) {
                $table->foreign('site_id')->references('id')->on('sites')->onDelete('set null');
            }
            if (Schema::hasTable('users')) {
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            }
            
            // Indexes
            $table->index('company_id');
            $table->index('status');
            $table->index('type');
            $table->index('generated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
