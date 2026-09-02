<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\EmissionRecord;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TenantTestCase;

/**
 * Supporting documents were written to the PUBLIC disk, which is served
 * unauthenticated.
 *
 * Under tenancy the public disk is not reachable through the public/storage
 * symlink — it lives in storage/tenant{id}/app/public — so it is served instead
 * by stancl's /tenancy/assets/{path} route. That route's only middleware is
 * InitializeTenancyBySubdomain: no `web`, no `auth`, no company check, and no
 * EnsureTenantIsActive, so even a SUSPENDED client's files stayed downloadable.
 *
 * downloadDocument() has always checked company ownership, but that check was
 * decorative while the same bytes sat behind an open route. These files are the
 * evidence an ISO 14064-3 assurer reads — utility invoices, meter readings,
 * supplier statements — and they carry account numbers and site addresses.
 *
 * The fix is which disk they land on, so that is what is asserted.
 */
class SupportingDocumentPrivacyTest extends TenantTestCase
{
    private Company $company;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $this->company = Company::create([
            'name' => 'Doc Privacy Co '.uniqid(),
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);

        $this->user = User::create([
            'name' => 'Doc Privacy User',
            'email' => 'doc-privacy-'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'company_id' => $this->company->id,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->user->givePermissionTo(['create-emission-record', 'list-emission-records']);

        $this->actingAs($this->user);
        app()->instance('current_company_id', $this->company->id);
        app()->instance('current_company', $this->company);
    }

    private function uploadRecordWithDocument(): EmissionRecord
    {
        $response = $this->post(route('emission-records.store'), [
            'entryDate' => '2025-07-01',
            'facilitySelect' => 'Plant A',
            'scopeSelect' => 1,
            'emissionSourceSelect' => 'Diesel Combustion',
            'co2eValue' => 12.5,
            'confidenceLevel' => 'high',
            'dataSource' => 'manual',
            'supporting_documents' => [
                UploadedFile::fake()->create('october-invoice.pdf', 40, 'application/pdf'),
            ],
        ]);

        $response->assertSuccessful();

        $record = EmissionRecord::where('company_id', $this->company->id)
            ->whereNotNull('supporting_documents')
            ->latest('id')
            ->first();

        $this->assertNotNull($record, 'The emission record was not created.');
        $this->assertIsArray($record->supporting_documents);
        $this->assertNotEmpty($record->supporting_documents, 'No supporting document was stored.');

        return $record;
    }

    public function test_a_supporting_document_is_not_written_to_the_publicly_served_disk(): void
    {
        $record = $this->uploadRecordWithDocument();
        $path = $record->supporting_documents[0];

        Storage::disk('public')->assertMissing($path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_the_owner_can_still_download_it(): void
    {
        $record = $this->uploadRecordWithDocument();

        $this->get(route('emission_records.document', ['emissionRecord' => $record->id, 'index' => 0]))
            ->assertOk();
    }

    public function test_another_company_cannot_download_it(): void
    {
        $record = $this->uploadRecordWithDocument();

        $otherCompany = Company::create([
            'name' => 'Other Doc Co '.uniqid(),
            'industry_type' => 'technology',
            'is_active' => true,
        ]);

        $intruder = User::create([
            'name' => 'Intruder',
            'email' => 'doc-intruder-'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'company_id' => $otherCompany->id,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $intruder->givePermissionTo(['list-emission-records']);

        $this->actingAs($intruder);
        app()->instance('current_company_id', $otherCompany->id);
        app()->instance('current_company', $otherCompany);

        $response = $this->get(
            route('emission_records.document', ['emissionRecord' => $record->id, 'index' => 0])
        );

        // 404, not the controller's 403: HasCompanyScope excludes the record
        // from the intruder's company, so route-model binding fails before
        // downloadDocument() runs. That is the better of the two — 403 confirms
        // the record exists, 404 says nothing. Both are accepted here so the
        // test pins the denial rather than which layer produced it.
        $this->assertContains(
            $response->status(),
            [403, 404],
            'Another company was served a supporting document it does not own.'
        );
    }

    /**
     * A document uploaded before the disk changed is still on 'public'. The
     * download action falls back to it so those keep working — asserted so the
     * fallback is not removed before the legacy files have actually been moved.
     */
    public function test_a_legacy_document_on_the_public_disk_is_still_served(): void
    {
        $legacyPath = 'supporting-documents/'.$this->company->id.'/2024/01/legacy-invoice.pdf';
        Storage::disk('public')->put($legacyPath, 'legacy bytes');

        $record = EmissionRecord::create([
            'company_id' => $this->company->id,
            'entry_date' => '2024-01-15',
            'facility' => 'Plant A',
            'scope' => 1,
            'emission_source' => 'Diesel Combustion',
            'co2e_value' => 3.2,
            'status' => 'active',
            'supporting_documents' => [$legacyPath],
        ]);

        $this->get(route('emission_records.document', ['emissionRecord' => $record->id, 'index' => 0]))
            ->assertOk();
    }
}
