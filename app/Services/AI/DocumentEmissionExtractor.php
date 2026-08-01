<?php

namespace App\Services\AI;

use App\Models\Company;
use App\Models\EmissionSource;
use App\Models\Scope3Category;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Phase 1 AI document extraction.
 *
 * Takes an uploaded document (invoice, utility/fuel bill, activity log,
 * spreadsheet, Word file, image or PDF) and asks Claude to pull out the
 * emission-relevant line items — each becoming a *draft* emission-record the
 * user reviews and approves. Nothing is persisted here.
 *
 * Handling by type:
 *   - PDF & images  -> sent to Claude natively (base64 document/image block),
 *                      so layout and scanned pages are read without a separate
 *                      OCR step.
 *   - Excel / CSV   -> parsed to a compact text table (PhpSpreadsheet).
 *   - Word (.docx)  -> text extracted from the OOXML body.
 *   - Plain text    -> passed through.
 *
 * Per the entry form's convention the emission factor is stored as tCO2e per
 * unit and CO2e = activity x factor. We ask the model for kgCO2e per unit (the
 * familiar basis) and convert to tonnes, mirroring NaturalLanguageEntryService.
 */
class DocumentEmissionExtractor
{
    public const PROMPT_VERSION = 'doc-extract-v1';

    /** Extensions we can turn into Claude content. */
    public const SUPPORTED = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'gif', 'xlsx', 'xls', 'csv', 'docx', 'txt'];

    public function __construct(
        protected ClaudeService $claude,
        protected FactorMatchingService $factors,
        protected SupplierMatchingService $suppliers,
    ) {
    }

    public function enabled(): bool
    {
        return $this->claude->enabled();
    }

    /**
     * Extract draft emission line items from an uploaded file.
     *
     * @return array{
     *   ok: bool,
     *   message: string,
     *   document_type: string|null,
     *   currency: string|null,
     *   line_items: array<int, array>,
     * }
     */
    public function extract(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());

        if (!$this->claude->enabled()) {
            return $this->fail('AI provider is not configured. Set ANTHROPIC_API_KEY to enable document extraction.');
        }

        if (!in_array($ext, self::SUPPORTED, true)) {
            return $this->fail("Unsupported file type: .{$ext}");
        }

        try {
            $content = $this->buildContent($file, $ext);
        } catch (\Throwable $e) {
            Log::warning('Document extraction: content build failed', ['error' => $e->getMessage()]);
            return $this->fail('Could not read the uploaded file: ' . $e->getMessage());
        }

        if (empty($content)) {
            return $this->fail('The file appears to be empty or unreadable.');
        }

        $categories = Scope3Category::orderBy('sort_order')->get(['id', 'name', 'sort_order', 'category_type']);

        // Known source names steer the model toward the company's real library,
        // so extracted names line up with factors we can lock to. Country drives
        // country-specific factor preference.
        $knownSources = EmissionSource::orderBy('name')->limit(300)->pluck('name')->filter()->values();
        $countryCode  = $this->companyCountryCode();

        // Prepend the instruction/schema text block, then the document block(s).
        array_unshift($content, $this->claude->textBlock($this->instruction($file->getClientOriginalName())));

        $result = $this->claude->jsonWithContent(
            $content,
            $this->systemPrompt($categories, $knownSources),
            ['temperature' => 0, 'max_tokens' => (int) config('services.anthropic.document_max_tokens', 8192)],
        );

        if (!is_array($result) || !isset($result['line_items']) || !is_array($result['line_items'])) {
            return $this->fail('The AI could not extract structured data from this document. Try a clearer file, or enter the data manually.');
        }

        $items = [];
        foreach ($result['line_items'] as $raw) {
            if (!is_array($raw)) {
                continue;
            }
            $item = $this->normalizeItem($raw, $categories);
            if ($item !== null) {
                $items[] = $this->applyLibraryFactor($item, $countryCode);
            }
        }

        if (empty($items)) {
            return $this->fail('No emission-relevant line items were found in this document.');
        }

        return [
            'ok'            => true,
            'message'       => count($items) . ' line item(s) extracted. Review, adjust, then save as drafts.',
            'document_type' => $this->clean($result['document_type'] ?? null, 60),
            'currency'      => $this->clean($result['currency'] ?? null, 3),
            'line_items'    => $items,
        ];
    }

    // ---------------------------------------------------------------------
    // Content preparation
    // ---------------------------------------------------------------------

    /**
     * Turn the uploaded file into an array of Claude content blocks.
     *
     * @return array<int, array>
     */
    protected function buildContent(UploadedFile $file, string $ext): array
    {
        $path = $file->getRealPath();

        // Native document/image handling (no OCR needed).
        if ($ext === 'pdf') {
            return [$this->claude->documentBlock(base64_encode(file_get_contents($path)))];
        }

        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $media = [
                'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif',
            ][$ext] ?? 'image/jpeg';

            // Downscale oversized images (e.g. hi-res screenshots) before sending.
            // Anthropic bills images by pixel area and resizes anything larger
            // than ~1568px anyway, so shrinking first cuts cost and latency with
            // no quality loss for text legibility.
            [$data, $media] = $this->prepareImage($path, $media);
            return [$this->claude->imageBlock($media, $data)];
        }

        // Structured / text formats -> convert to a text block.
        $text = match ($ext) {
            'xlsx', 'xls', 'csv' => $this->spreadsheetToText($path),
            'docx'               => $this->docxToText($path),
            default              => (string) file_get_contents($path), // txt
        };

        $text = trim($text);
        if ($text === '') {
            return [];
        }

        // Guard prompt size — keep the first ~60k characters.
        return [$this->claude->textBlock(Str::limit($text, 60000, "\n…(truncated)"))];
    }

    /**
     * Return [base64, mediaType] for an image, downscaling it to a max dimension
     * of 1568px when larger. Falls back to the original bytes if GD is missing
     * or anything goes wrong — extraction must never fail on the resize step.
     *
     * @return array{0:string,1:string}
     */
    protected function prepareImage(string $path, string $media): array
    {
        $original = [base64_encode(file_get_contents($path)), $media];

        if (!function_exists('imagecreatefromstring')) {
            return $original;
        }

        try {
            $max = 1568;
            $size = @getimagesize($path);
            if (!$size) {
                return $original;
            }
            [$w, $h] = $size;
            if ($w <= $max && $h <= $max) {
                return $original;                       // already small enough
            }

            $img = @imagecreatefromstring(file_get_contents($path));
            if (!$img) {
                return $original;
            }

            $scale = $max / max($w, $h);
            $nw = (int) round($w * $scale);
            $nh = (int) round($h * $scale);

            $dst = imagecreatetruecolor($nw, $nh);
            // Preserve transparency for PNG/GIF/WEBP.
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

            ob_start();
            // Re-encode as PNG (lossless — keeps text crisp for OCR-like reading).
            imagepng($dst, null, 6);
            $bytes = ob_get_clean();

            imagedestroy($img);
            imagedestroy($dst);

            if ($bytes === false || $bytes === '') {
                return $original;
            }

            return [base64_encode($bytes), 'image/png'];
        } catch (\Throwable $e) {
            Log::warning('Image downscale failed, sending original: ' . $e->getMessage());
            return $original;
        }
    }

    /**
     * Render a spreadsheet (all sheets) as a compact pipe-delimited text table.
     */
    protected function spreadsheetToText(string $path): string
    {
        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            // maatwebsite/excel bundles PhpSpreadsheet; this is defensive only.
            return (string) file_get_contents($path);
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
        $out = [];

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $title = $sheet->getTitle();
            $out[] = "### Sheet: {$title}";
            foreach ($sheet->toArray(null, true, false, false) as $row) {
                // Skip fully empty rows.
                $cells = array_map(fn ($c) => trim((string) $c), $row);
                if (implode('', $cells) === '') {
                    continue;
                }
                $out[] = implode(' | ', $cells);
            }
            $out[] = '';
        }

        return implode("\n", $out);
    }

    /**
     * Extract readable text from a .docx (OOXML) file without extra libraries.
     */
    protected function docxToText(string $path): string
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return '';
        }

        $xml = $zip->getFromName('word/document.xml') ?: '';
        $zip->close();

        if ($xml === '') {
            return '';
        }

        // Paragraph and tab boundaries -> whitespace, then strip all tags.
        $xml = preg_replace('/<w:tab[^>]*\/>/', "\t", $xml);
        $xml = preg_replace('/<\/w:p>/', "\n", $xml);
        $text = strip_tags($xml);

        return html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    // ---------------------------------------------------------------------
    // Prompt
    // ---------------------------------------------------------------------

    protected function instruction(string $filename): string
    {
        $today = now()->toDateString();
        return "Extract every emission-relevant activity line item from the attached document (filename: \"{$filename}\"). "
            . "Today's date is {$today}. If the document is an invoice or bill, treat each purchased quantity of energy, fuel, "
            . "goods, or travel as a separate line item. Do not invent items that are not supported by the document.";
    }

    protected function systemPrompt($categories, $knownSources = null): string
    {
        $categoryList = $categories
            ->map(fn ($c) => "{$c->sort_order}. {$c->name} ({$c->category_type})")
            ->implode("\n");

        $knownBlock = '';
        if ($knownSources && $knownSources->isNotEmpty()) {
            $list = $knownSources->map(fn ($n) => "- {$n}")->implode("\n");
            $knownBlock = "\n\nKNOWN EMISSION SOURCES (the company's library). If a line item clearly corresponds to one of these, "
                . "set emission_source to EXACTLY that name (verbatim) so the correct stored factor can be applied. "
                . "Only use your own name when none of these fit:\n{$list}";
        }

        return <<<SYS
You are a GHG accounting assistant extracting structured emission data from business documents (invoices, utility/fuel bills, receipts, activity logs, spreadsheets). Follow the GHG Protocol Corporate Standard.

Scopes:
- Scope 1: direct emissions from owned/controlled sources (on-site fuel combustion, company vehicles/fleet, fugitive refrigerant leaks, industrial processes).
- Scope 2: indirect emissions from purchased energy consumed (electricity, district heating/cooling, steam).
- Scope 3: other indirect value-chain emissions, in 15 categories:
{$categoryList}

For EACH emission-relevant line item, extract:
- activity_description: short human label (e.g. "Diesel fuel purchase", "Grid electricity", "Business flight LHR-DXB").
- quantity: numeric amount of activity (number only), or null if not stated.
- unit: the unit of that quantity as printed (e.g. "liters", "kWh", "m3", "km", "kg", "night", "unit"). Keep it simple and singular.
- suggested_scope: 1, 2, or 3.
- scope3_category_number: 1-15 when scope is 3, else null.
- emission_source: concise source name (e.g. "Diesel (Stationary)", "Purchased Electricity (Location-based)").
- factor_kg_per_unit: best-practice emission factor in kgCO2e per the chosen unit (e.g. diesel ~2.68/litre, petrol ~2.31/litre, natural gas ~0.18/kWh, grid electricity ~0.4/kWh). Give your best authoritative estimate, or null if you cannot.
- factor_source: short citation ("DEFRA 2024", "IPCC", "IEA grid avg") or "AI estimate".
- date: activity/invoice date as YYYY-MM-DD, or null.
- supplier_name: vendor/supplier/station name, or null.
- confidence: 0.0-1.0 for THIS line item.
- source_snippet: the exact text from the document this line item was read from (short), so a human can verify it.

Rules:
- Only extract activities that cause emissions. Ignore taxes, discounts, subtotals, account numbers, and non-emitting line items.
- Convert obvious unit variants but keep the number faithful to the document (e.g. gallons -> liters at 3.78541). State the converted unit.
- A vehicle/car is Scope 1 only if company-owned; if unclear (rental, personal, taxi), classify as Scope 3 (business travel) and lower confidence.
- Never fabricate quantities. If a quantity is not in the document, set it null and lower confidence.{$knownBlock}

Return JSON with exactly:
{
  "document_type": string,        // e.g. "electricity_bill", "fuel_invoice", "flight_receipt", "activity_log"
  "currency": string|null,        // ISO 4217 if monetary amounts appear, else null
  "line_items": [ { ...fields above... } ]
}
SYS;
    }

    // ---------------------------------------------------------------------
    // Normalisation (mirrors NaturalLanguageEntryService conventions)
    // ---------------------------------------------------------------------

    protected function normalizeItem(array $raw, $categories): ?array
    {
        $scope = (int) ($raw['suggested_scope'] ?? 0);
        if (!in_array($scope, [1, 2, 3], true)) {
            return null;
        }

        $catNumber = $scope === 3 && is_numeric($raw['scope3_category_number'] ?? null)
            ? (int) $raw['scope3_category_number']
            : null;
        $category = $catNumber ? $categories->firstWhere('sort_order', $catNumber) : null;

        $quantity = is_numeric($raw['quantity'] ?? null) ? (float) $raw['quantity'] : null;
        $factorKg = is_numeric($raw['factor_kg_per_unit'] ?? null) ? (float) $raw['factor_kg_per_unit'] : null;
        $factorT  = $factorKg !== null ? $factorKg / 1000 : null;                 // tCO2e per unit
        $co2e     = ($quantity !== null && $factorT !== null) ? round($quantity * $factorT, 4) : null;

        $confidence = max(0.0, min(1.0, (float) ($raw['confidence'] ?? 0.5)));

        return [
            'date'                   => $this->normalizeDate($raw['date'] ?? null),
            'scope'                  => $scope,
            'scope3_category_id'     => $category?->id,
            'scope3_category_number' => $category?->sort_order ?? ($scope === 3 ? $catNumber : null),
            'scope3_category_name'   => $category?->name,
            'emission_source'        => $this->clean($raw['emission_source'] ?? ($raw['activity_description'] ?? null), 100) ?? 'Unspecified',
            'activity_description'   => $this->clean($raw['activity_description'] ?? null, 200),
            'activity_data'          => $quantity,
            'unit'                   => $this->clean($raw['unit'] ?? null, 20) ?? '',
            'factor_kg_per_unit'     => $factorKg !== null ? round($factorKg, 6) : null,
            'emission_factor'        => $factorT !== null ? round($factorT, 8) : null,
            'co2e_value'             => $co2e,
            'supplier_name'          => $this->clean($raw['supplier_name'] ?? null, 150),
            'confidence'             => round($confidence, 2),
            'confidence_level'       => $this->confidenceLevel($confidence),
            'factor_note'            => $this->clean($raw['factor_source'] ?? null, 80) ?? 'AI estimate',
            'source_snippet'         => $this->clean($raw['source_snippet'] ?? null, 300),
            // Provenance — overwritten by applyLibraryFactor() when a stored
            // factor is matched. 'needs_review' surfaces rows a human must check.
            'emission_factor_id'     => null,
            'factor_basis'           => 'ai_estimated',
            'factor_matched'         => false,
            'needs_review'           => true,
            // Scope 3 supplier link — filled by applyLibraryFactor().
            'supplier_id'            => null,
            'supplier_matched'       => false,
        ];
    }

    /**
     * If the extracted source maps to a stored EmissionFactor, replace the AI's
     * estimated factor with the authoritative one, recompute CO2e, and lock the
     * factor id so EmissionEnrichmentService records exact provenance on save.
     */
    protected function applyLibraryFactor(array $item, ?string $countryCode): array
    {
        $match = $this->factors->match($item['emission_source'], $item['unit'] ?? null, $countryCode);

        if ($match) {
            $factorT = (float) $match['factor_value'];                       // tCO2e per unit
            $item['emission_factor']    = round($factorT, 8);
            $item['factor_kg_per_unit'] = round($factorT * 1000, 6);         // for display
            if ($item['activity_data'] !== null) {
                $item['co2e_value'] = round((float) $item['activity_data'] * $factorT, 4);
            }
            $item['emission_factor_id'] = $match['emission_factor_id'];
            $item['factor_basis']       = 'library';
            $item['factor_matched']     = true;
            $item['factor_note']        = $match['dataset']
                ?? ($match['organization'] ? $match['organization'] . ' (library)' : 'Company factor library');
        }

        // Scope 3: try to link the extracted supplier to an existing record.
        if ((int) $item['scope'] === 3 && !empty($item['supplier_name'])) {
            $supplier = $this->suppliers->match($item['supplier_name']);
            if ($supplier) {
                $item['supplier_id']      = $supplier['supplier_id'];
                $item['supplier_matched'] = true;
                $item['supplier_name']    = $supplier['name']; // canonical spelling
            }
        }

        // A row needs human review when the factor is a guess OR confidence is
        // not high OR no quantity was found. Matched, high-confidence, quantified
        // rows are the only ones considered clean.
        $item['needs_review'] = !$item['factor_matched']
            || $item['confidence_level'] !== 'high'
            || $item['activity_data'] === null;

        return $item;
    }

    /** Company's factor region code (e.g. "AE") for country-specific factors. */
    protected function companyCountryCode(): ?string
    {
        $companyId = app()->bound('current_company_id')
            ? app('current_company_id')
            : auth()->user()?->company_id;

        if (!$companyId) {
            return null;
        }

        $display = Company::find($companyId)?->country;
        if (!$display) {
            return null;
        }

        return function_exists('country_to_factor_region')
            ? country_to_factor_region($display)
            : $display;
    }

    protected function normalizeDate($value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }
        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function confidenceLevel(float $c): string
    {
        // Clamp to the three DB-backed levels (low|medium|high).
        return match (true) {
            $c >= 0.8 => 'high',
            $c >= 0.5 => 'medium',
            default   => 'low',
        };
    }

    protected function clean($v, int $max): ?string
    {
        if (!is_string($v) || trim($v) === '') {
            return null;
        }
        return Str::limit(trim($v), $max, '…');
    }

    protected function fail(string $message): array
    {
        return [
            'ok'            => false,
            'message'       => $message,
            'document_type' => null,
            'currency'      => null,
            'line_items'    => [],
        ];
    }
}
