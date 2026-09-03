<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Facilities;
use App\Models\User;
use App\Services\MRV\EadWorkbookFiller;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use Tests\TenantTestCase;

/**
 * The EAD workbook template is shared reference data, not a client's file.
 *
 * EadWorkbookFiller resolved it as storage_path('app/templates/…'), which was
 * right before tenancy and wrong after it. FilesystemTenancyBootstrapper
 * suffixes storage_path() per tenant, and this service is only ever reached
 * from inside a tenant request — the application is served exclusively on
 * tenant subdomains — so the path resolved to
 * storage/tenant{id}/app/templates/…: a directory that holds one client's
 * uploaded bills and has never contained the template.
 *
 * The result was that EVERY EAD export threw, on every tenant, since the
 * tenancy conversion. The feature was not partly working; it was dead.
 *
 * Two things are pinned here: that the path escapes per-tenant storage, and
 * that a template which genuinely is not installed fails legibly rather than
 * as a 500 with a server filesystem path in it.
 */
class EadTemplatePathTest extends TenantTestCase
{
    private function actAsReportUser(): Company
    {
        $company = Company::create([
            'name' => 'EAD Export Co '.uniqid(),
            'industry_type' => 'energy',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'MRV Officer',
            'email' => 'mrv-'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->givePermissionTo(['list-reports', 'create-report']);

        $this->actingAs($user);
        app()->instance('current_company_id', $company->id);
        app()->instance('current_company', $company);

        return $company;
    }

    public function test_the_template_is_not_looked_for_inside_a_tenants_own_storage(): void
    {
        // Precondition: prove this environment really does suffix storage_path,
        // so the assertion below is testing something. Without this the test
        // would pass vacuously on any setup where tenancy was not initialised.
        $this->assertStringContainsString(
            self::TEST_TENANT_ID,
            storage_path('app'),
            'Tenancy is not suffixing storage_path — this test would prove nothing.'
        );

        $path = str_replace('\\', '/', EadWorkbookFiller::templatePath());

        $this->assertStringNotContainsString(
            'tenant'.self::TEST_TENANT_ID,
            $path,
            'The shared EAD template was resolved inside one client’s private storage directory.'
        );

        // And it is the shared location, not merely some other tenant's.
        $this->assertStringContainsString('storage/app/templates/ead_deliverable_c.xlsx', $path);
    }

    public function test_the_path_is_identical_for_every_tenant(): void
    {
        $inTenant = EadWorkbookFiller::templatePath();

        tenancy()->end();
        $outsideTenant = EadWorkbookFiller::templatePath();

        tenancy()->initialize(\App\Models\Tenant::find(self::TEST_TENANT_ID));

        $this->assertSame(
            $outsideTenant,
            $inTenant,
            'The template is byte-identical for every client, so its path must be too.'
        );
    }

    public function test_a_deployment_can_point_at_the_template_ead_issued_it(): void
    {
        // The cell map is pinned to v8.1, and EAD issues the file per operator
        // under a confidentiality notice — so the location has to be settable
        // rather than baked in.
        config(['mrv.ead_template' => '/opt/ead/deliverable_c_v9.xlsx']);

        $this->assertSame('/opt/ead/deliverable_c_v9.xlsx', EadWorkbookFiller::templatePath());
    }

    public function test_a_missing_template_names_the_path_it_wanted(): void
    {
        config(['mrv.ead_template' => storage_path('app/definitely-not-here.xlsx')]);

        $facility = Facilities::create([
            'company_id' => $this->actAsReportUser()->id,
            'name' => 'Ruwais Plant',
            'mrv_enabled' => true,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/definitely-not-here\.xlsx/');

        app(EadWorkbookFiller::class)->fill($facility, 2026);
    }

    public function test_the_export_screen_reports_a_missing_template_instead_of_crashing(): void
    {
        $company = $this->actAsReportUser();
        config(['mrv.ead_template' => storage_path('app/definitely-not-here.xlsx')]);

        $facility = Facilities::create([
            'company_id' => $company->id,
            'name' => 'Ruwais Plant',
            'mrv_enabled' => true,
        ]);

        $response = $this->get(route('mrv.export', ['facility_id' => $facility->id, 'year' => 2026]));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        // The message says what to do; it does not hand a client's staff a
        // filesystem path off the server.
        $this->assertStringNotContainsString('definitely-not-here', session('error'));
        $this->assertStringContainsString('administrator', session('error'));
    }

    public function test_the_real_template_loads_from_inside_a_tenant_request(): void
    {
        $path = EadWorkbookFiller::templatePath();

        if (! is_file($path)) {
            // Not shipped with the application by design — see config/mrv.php.
            $this->markTestSkipped('EAD template not installed at '.$path);
        }

        $facility = Facilities::create([
            'company_id' => $this->actAsReportUser()->id,
            'name' => 'Ruwais Plant',
            'mrv_enabled' => true,
            'environmental_permit_no' => 'EP-2026-118',
        ]);

        $book = app(EadWorkbookFiller::class)->fill($facility, 2026);

        // The template's own sheets, proving EAD's workbook was loaded rather
        // than a blank spreadsheet being produced.
        $this->assertNotNull($book->getSheetByName('2c1_ Identifiers'));
        $this->assertNotNull($book->getSheetByName('2c2_Facility Description'));
        $this->assertSame('Ruwais Plant', $book->getSheetByName('2c1_ Identifiers')->getCell('H7')->getValue());
    }
}
