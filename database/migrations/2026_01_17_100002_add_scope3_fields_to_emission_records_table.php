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
        Schema::table('emission_records', function (Blueprint $table) {
            // Add Scope 3 specific fields
            $table->foreignId('scope3_category_id')->nullable()->after('scope')
                ->constrained('scope3_categories')->onDelete('set null');
            
            $table->foreignId('supplier_id')->nullable()->after('scope3_category_id')
                ->constrained('suppliers')->onDelete('set null');
            
            $table->enum('calculation_method', ['activity-based', 'spend-based', 'hybrid'])
                ->nullable()->after('emission_factor');
            
            $table->enum('data_quality', ['primary', 'secondary', 'estimated'])
                ->default('estimated')->after('calculation_method');
            
            $table->decimal('spend_amount', 15, 2)->nullable()->after('activity_data');
            $table->string('spend_currency', 3)->default('USD')->after('spend_amount');
            
            $table->json('supporting_documents')->nullable()->after('notes');
            
            // Add indexes
            $table->index('scope3_category_id');
            $table->index('supplier_id');
            $table->index('calculation_method');
            $table->index('data_quality');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emission_records', function (Blueprint $table) {
            $table->dropForeign(['scope3_category_id']);
            $table->dropForeign(['supplier_id']);
            $table->dropColumn([
                'scope3_category_id',
                'supplier_id',
                'calculation_method',
                'data_quality',
                'spend_amount',
                'spend_currency',
                'supporting_documents'
            ]);
        });
    }
};
