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
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('report_template_id')->nullable();
            $table->unsignedInteger('facility_id')->nullable();
            $table->unsignedInteger('department_id')->nullable();
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->time('schedule_time')->default('08:00:00');
            $table->date('next_run_date')->nullable();
            $table->date('last_run_date')->nullable();
            $table->json('recipients')->nullable(); // Array of email addresses
            $table->json('formats')->nullable(); // ['pdf', 'excel', 'pptx']
            $table->enum('status', ['active', 'paused', 'completed'])->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            if (Schema::hasTable('report_templates')) {
                $table->foreign('report_template_id')->references('id')->on('report_templates')->onDelete('set null');
            }
            if (Schema::hasTable('facilities')) {
                $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('set null');
            }
            if (Schema::hasTable('departments')) {
                $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            }
            if (Schema::hasTable('users')) {
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            }

            $table->index('status');
            $table->index('next_run_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_reports');
    }
};

