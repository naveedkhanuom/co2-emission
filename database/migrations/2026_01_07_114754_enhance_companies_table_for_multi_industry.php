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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('code', 50)->unique()->nullable()->after('name')->comment('Company code/identifier');
            $table->enum('industry_type', [
                'manufacturing', 'energy', 'transportation', 'agriculture', 
                'construction', 'retail', 'healthcare', 'education', 
                'technology', 'finance', 'hospitality', 'mining', 
                'chemical', 'textile', 'food_beverage', 'other'
            ])->nullable()->change();
            $table->string('tax_id', 100)->nullable()->after('industry_type');
            $table->string('registration_number', 100)->nullable()->after('tax_id');
            $table->string('website')->nullable()->after('phone');
            $table->string('logo')->nullable()->after('website');
            $table->enum('size', ['small', 'medium', 'large', 'enterprise'])->nullable()->after('logo');
            $table->integer('employee_count')->nullable()->after('size');
            $table->decimal('annual_revenue', 15, 2)->nullable()->after('employee_count');
            $table->string('currency', 3)->default('USD')->after('annual_revenue');
            $table->string('timezone', 50)->default('UTC')->after('currency');
            $table->string('fiscal_year_start', 10)->nullable()->after('timezone')->comment('MM-DD format');
            $table->json('reporting_standards')->nullable()->after('fiscal_year_start')->comment('GHG Protocol, ISO 14064, etc.');
            $table->json('scopes_enabled')->nullable()->after('reporting_standards')->comment('Which scopes are enabled [1,2,3]');
            $table->boolean('is_active')->default(true)->after('scopes_enabled');
            $table->timestamp('subscription_expires_at')->nullable()->after('is_active');
            $table->text('notes')->nullable()->after('subscription_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'code', 'tax_id', 'registration_number', 'website', 'logo',
                'size', 'employee_count', 'annual_revenue', 'currency',
                'timezone', 'fiscal_year_start', 'reporting_standards',
                'scopes_enabled', 'is_active', 'subscription_expires_at', 'notes'
            ]);
        });
    }
};
