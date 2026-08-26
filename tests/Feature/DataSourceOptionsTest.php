<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\EmissionRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Tests\TenantTestCase;

/**
 * GHG-10 — every data source the entry forms offer must be storable.
 *
 * The Manual Entry and Scope-Based Entry forms both offer "How was this data
 * obtained?" with Meter Reading, Utility Invoice and Estimate. The controller
 * validated all three, but the data_source column was
 * ENUM('manual','import','api','supplier-survey') and sql_mode includes
 * STRICT_TRANS_TABLES — so three of the four options failed on save.
 */
class DataSourceOptionsTest extends TenantTestCase
{
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'DataSource Co '.uniqid(),
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'DataSource User',
            'email' => 'datasource-'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->givePermissionTo(['create-emission-record', 'list-emission-records']);

        $this->actingAs($user);
        app()->instance('current_company_id', $this->company->id);
        app()->instance('current_company', $this->company);
    }

    public static function offeredDataSources(): array
    {
        return [
            'manual entry' => ['manual'],
            'meter reading' => ['meter'],
            'utility invoice' => ['invoice'],
            'estimate' => ['estimate'],
        ];
    }

    /**
     * @dataProvider offeredDataSources
     */
    public function test_each_offered_data_source_saves(string $dataSource): void
    {
        $this->postJson(route('emission-records.store'), [
            'entryDate' => '2025-07-01',
            'facilitySelect' => 'Plant A',
            'scopeSelect' => 1,
            'emissionSourceSelect' => 'Diesel (Stationary)',
            'activityData' => 1000,
            'activityUnit' => 'liters',
            'co2eValue' => 2.6847,
            'confidenceLevel' => 'medium',
            'dataSource' => $dataSource,
        ])->assertOk();

        $this->assertSame(
            $dataSource,
            EmissionRecord::where('company_id', $this->company->id)->latest('id')->firstOrFail()->data_source
        );
    }

    /**
     * The column and the validation rules must not drift apart again: every
     * value the controller accepts has to be a member of the enum.
     */
    public function test_the_column_accepts_every_validated_value(): void
    {
        $column = DB::selectOne(
            "SELECT COLUMN_TYPE t FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'emission_records'
               AND COLUMN_NAME = 'data_source'"
        )->t;

        $validated = ['manual', 'import', 'api', 'supplier-survey', 'meter', 'invoice', 'estimate'];

        foreach ($validated as $value) {
            $this->assertStringContainsString(
                "'{$value}'",
                $column,
                "data_source validation accepts '{$value}' but the column cannot store it."
            );
        }
    }
}
