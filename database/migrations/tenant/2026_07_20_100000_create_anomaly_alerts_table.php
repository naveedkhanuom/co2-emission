<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records each emissions anomaly the scanner has already alerted on, so the
 * daily job is idempotent — the same spike in the same month is notified once,
 * never every day. Also serves as an audit trail of what was detected.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anomaly_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('type');            // emission_spike | new_source | quality_drop
            $table->string('anomaly_key');     // stable identity incl. period, for dedup
            $table->string('period', 7);       // YYYY-MM the anomaly belongs to
            $table->string('title');
            $table->text('message');
            $table->string('severity', 20)->default('warning');
            $table->json('metadata')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            // One alert per anomaly per company — the key already encodes the period.
            $table->unique(['company_id', 'anomaly_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anomaly_alerts');
    }
};
