<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;
use Tests\TenantTestCase;

/**
 * `GET /companies/{id}` serves two callers, and only one of them worked.
 *
 * The edit modal on the companies index fetches this URL with
 * `Accept: application/json` and fills its fields from the response. The View
 * button next to it does `window.location.href = /companies/{id}` — a plain
 * browser navigation — and the controller answered that with
 * `response()->json($company)` too.
 *
 * So clicking View rendered a raw JSON dump as text: the company's address,
 * contact person, email, phone, tax id and registration number, in a browser
 * window, instead of a page.
 *
 * Both behaviours are pinned here, because fixing one by breaking the other is
 * the obvious wrong move — the edit modal has no other source for its data.
 */
class CompanyShowPageTest extends TenantTestCase
{
    private function makeCompany(): Company
    {
        return Company::create([
            'name' => 'Show Test Co '.uniqid(),
            'code' => 'STC',
            'industry_type' => 'technology',
            'address' => 'Swiss Arabian Building, Abu Dhabi',
            'contact_person' => 'A Person',
            'tax_id' => 'ID2344',
            'reporting_standards' => ['GHG Protocol', 'ISO 14064'],
            'scopes_enabled' => [1, 2, 3],
            'is_active' => true,
        ]);
    }

    private function actAsMemberOf(Company $company): User
    {
        $user = User::create([
            'name' => 'Company Viewer',
            'email' => 'company-viewer-'.uniqid().'@example.test',
            'password' => bcrypt('password'),
            'company_id' => $company->id,
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->givePermissionTo(['list-companies']);

        $this->actingAs($user);
        app()->instance('current_company_id', $company->id);
        app()->instance('current_company', $company);

        return $user;
    }

    public function test_a_browser_navigation_renders_a_page_not_json(): void
    {
        $company = $this->makeCompany();
        $this->actAsMemberOf($company);

        $response = $this->get(route('companies.show', $company->id));

        $response->assertOk();
        $response->assertViewIs('companies.show');
        $response->assertSee($company->name);

        // The symptom itself: the body used to BE the JSON document.
        $this->assertStringNotContainsString(
            '{"id":'.$company->id.',"name":',
            $response->getContent(),
            'The company was served as a raw JSON dump instead of a page.'
        );
    }

    public function test_an_ajax_request_still_receives_json(): void
    {
        // The edit modal depends on this. Breaking it to fix the page above
        // would trade one broken screen for another.
        $company = $this->makeCompany();
        $this->actAsMemberOf($company);

        $this->getJson(route('companies.show', $company->id))
            ->assertOk()
            ->assertJson([
                'id' => $company->id,
                'name' => $company->name,
                'code' => 'STC',
            ]);
    }

    public function test_a_company_the_user_cannot_access_is_refused_either_way(): void
    {
        $company = $this->makeCompany();
        $foreign = Company::create([
            'name' => 'Foreign Co '.uniqid(),
            'industry_type' => 'manufacturing',
            'is_active' => true,
        ]);

        $this->actAsMemberOf($company);

        $this->get(route('companies.show', $foreign->id))->assertForbidden();
        $this->getJson(route('companies.show', $foreign->id))->assertForbidden();
    }
}
