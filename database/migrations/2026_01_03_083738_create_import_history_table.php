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
        Schema::create('import_history', function (Blueprint $table) {
            $table->id();
            $table->string('import_id', 50)->unique()->index(); // IMP-1024 format
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedBigInteger('file_size')->nullable(); // in bytes
            $table->enum('import_type', ['csv', 'excel', 'api', 'manual', 'scheduled'])->default('csv');
            $table->enum('status', ['queued', 'processing', 'completed', 'failed', 'partial'])->default('queued');
            $table->unsignedInteger('total_records')->default(0);
            $table->unsignedInteger('successful_records')->default(0);
            $table->unsignedInteger('failed_records')->default(0);
            $table->unsignedInteger('warning_records')->default(0);
            $table->decimal('processing_time', 8, 2)->nullable(); // in seconds
            $table->text('logs')->nullable(); // JSON or text logs
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable(); // Additional data like mapping, settings
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index('status');
            $table->index('import_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_history');
    }
};
