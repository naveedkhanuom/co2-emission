<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TenantTestCase;

/**
 * TEN-17 — moving the legacy documents off the publicly-served disk.
 *
 * New uploads land on `local`, but files uploaded before that change are still
 * on `public`, which /tenancy/assets/{path} serves with no authentication. The
 * readers fall back so those files keep working — which means the exposure is
 * not closed until they are actually moved.
 *
 * This command moves them. It runs against files nobody has a second copy of, so
 * the behaviour that matters most is what it does NOT do: it must not delete a
 * public file before the private copy is verified, and it must not touch logos,
 * which have to stay publicly readable for the login screen.
 */
class PrivatiseStoredDocumentsTest extends TenantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    private function privatise(array $options = []): int
    {
        return $this->artisan('documents:privatise', $options)->run();
    }

    public function test_it_moves_a_supporting_document_to_the_private_disk(): void
    {
        Storage::disk('public')->put('supporting-documents/1/2024/01/invoice.pdf', 'invoice bytes');

        $this->privatise();

        Storage::disk('local')->assertExists('supporting-documents/1/2024/01/invoice.pdf');
        Storage::disk('public')->assertMissing('supporting-documents/1/2024/01/invoice.pdf');
        $this->assertSame('invoice bytes', Storage::disk('local')->get('supporting-documents/1/2024/01/invoice.pdf'));
    }

    public function test_it_moves_energy_certificates_too(): void
    {
        Storage::disk('public')->put('energy-certificates/1/2024/01/rec.pdf', 'rec bytes');

        $this->privatise();

        Storage::disk('local')->assertExists('energy-certificates/1/2024/01/rec.pdf');
        Storage::disk('public')->assertMissing('energy-certificates/1/2024/01/rec.pdf');
    }

    /**
     * Logos render on the UNAUTHENTICATED login screen. Making them private
     * would break sign-in branding for every client, so they must be left alone.
     */
    public function test_it_leaves_logos_on_the_public_disk(): void
    {
        Storage::disk('public')->put('company_logos/acme.png', 'logo bytes');
        Storage::disk('public')->put('app/brand.png', 'brand bytes');

        $this->privatise();

        Storage::disk('public')->assertExists('company_logos/acme.png');
        Storage::disk('public')->assertExists('app/brand.png');
        Storage::disk('local')->assertMissing('company_logos/acme.png');
    }

    public function test_pretend_changes_nothing(): void
    {
        Storage::disk('public')->put('supporting-documents/1/2024/01/invoice.pdf', 'invoice bytes');

        $this->privatise(['--pretend' => true]);

        Storage::disk('public')->assertExists('supporting-documents/1/2024/01/invoice.pdf');
        Storage::disk('local')->assertMissing('supporting-documents/1/2024/01/invoice.pdf');
    }

    /**
     * Re-running must be safe. A file already on `local` means the reader would
     * find the private copy first, so the public leftover is deleted rather than
     * copied over the top of it.
     */
    public function test_it_is_safe_to_run_twice(): void
    {
        Storage::disk('public')->put('supporting-documents/1/2024/01/invoice.pdf', 'invoice bytes');

        $this->privatise();
        $this->privatise();

        Storage::disk('local')->assertExists('supporting-documents/1/2024/01/invoice.pdf');
        $this->assertSame('invoice bytes', Storage::disk('local')->get('supporting-documents/1/2024/01/invoice.pdf'));
    }

    /**
     * A public leftover alongside an existing private copy must not overwrite
     * the private one — that copy is the live file the app already serves.
     */
    public function test_it_does_not_overwrite_an_existing_private_copy(): void
    {
        Storage::disk('local')->put('supporting-documents/1/2024/01/invoice.pdf', 'the real one');
        Storage::disk('public')->put('supporting-documents/1/2024/01/invoice.pdf', 'stale duplicate');

        $this->privatise();

        $this->assertSame('the real one', Storage::disk('local')->get('supporting-documents/1/2024/01/invoice.pdf'));
        Storage::disk('public')->assertMissing('supporting-documents/1/2024/01/invoice.pdf');
    }
}
