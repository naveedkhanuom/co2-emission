<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Energy Attribute Certificates (EACs) and other market-based instruments.
 *
 * Required for GHG Protocol Scope 2 Guidance "market-based" reporting: RECs,
 * Guarantees of Origin (GOs), I-RECs, PPAs/VPPAs, green tariffs, supplier-
 * specific factors and residual-mix factors. A Scope 2 emission record can be
 * linked to one of these to derive its market-based figure; without a contract
 * the residual mix (or grid) factor applies.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('energy_attribute_certificates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();

            // rec | go | irec | ppa | vppa | green_tariff | supplier_specific | residual_mix
            $table->string('type', 30)->default('rec');
            $table->string('name');
            $table->string('certificate_number')->nullable();
            $table->string('supplier_name')->nullable();
            $table->string('energy_carrier', 20)->default('electricity'); // electricity | heat | steam | cooling

            // Contracted volume and the market-based emission factor it carries.
            // Renewables backed by a valid certificate carry 0 kgCO2e/kWh.
            $table->decimal('mwh_volume', 18, 4)->default(0);
            $table->decimal('emission_factor', 12, 6)->default(0)->comment('kgCO2e per kWh (market-based)');

            $table->string('region')->nullable();
            $table->smallInteger('vintage_year')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->date('retired_at')->nullable();

            // active | retired | expired | cancelled
            $table->string('status', 20)->default('active');

            $table->string('document_path')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('energy_attribute_certificates');
    }
};
