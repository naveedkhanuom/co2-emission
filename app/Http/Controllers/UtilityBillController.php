<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\UtilityBill;
use App\Models\EmissionRecord;
use App\Models\EmissionSource;
use App\Models\EmissionFactor;
use App\Models\Facilities;
use App\Models\Department;
use Illuminate\Support\Facades\Auth;
use App\Services\BillDataExtractor;
use thiagoalessio\TesseractOCR\TesseractOCR;

class UtilityBillController extends Controller
{
    protected $extractor;

    public function __construct(BillDataExtractor $extractor)
    {
        $this->middleware('auth');
        $this->middleware('permission:list-utility-bills|create-utility-bill|edit-utility-bill|delete-utility-bill', ['only' => ['index', 'create']]);
        $this->middleware('permission:create-utility-bill', ['only' => ['upload']]);
        $this->extractor = $extractor;
    }

    // Show upload form
    public function create()
    {
        $facilities = Facilities::all();
        $departments = Department::all();
        $emissionSources = EmissionSource::all();
        
        return view('utility_bills.create', compact('facilities', 'departments', 'emissionSources'));
    }

    // Show uploaded utility bills
    public function index()
    {
        $bills = UtilityBill::with(['company', 'site', 'uploader'])->latest()->get();
        return view('utility_bills.index', compact('bills'));
    }

    /**
     * Stream a bill file to authorized users only. Bills live on the private
     * disk (not web-accessible); this is the only way to view one. Route-model
     * binding is company-scoped via HasCompanyScope, so a user cannot bind
     * another company's bill; the explicit check guards super-admins too.
     * Falls back to the legacy public disk for files uploaded before the move.
     */
    public function download(UtilityBill $utilityBill)
    {
        $user = Auth::user();
        if (! ($user && $user->is_super_admin) && $utilityBill->company_id != current_company_id()) {
            abort(403, 'You do not have access to this file.');
        }

        $path = $utilityBill->file_path;
        if (! $path) {
            abort(404);
        }

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->download($path);
            }
        }

        abort(404, 'File not found.');
    }

    // Upload and process utility bill
    public function upload(Request $request)
    {
        $request->validate([
            'bill_file' => 'required|mimes:jpg,jpeg,png,pdf|max:10240',
            'bill_type' => 'required|in:electricity,fuel',
            'facility_id' => 'required|exists:facilities,id',
            'department_id' => 'nullable|exists:departments,id',
            'emission_source' => 'required|string',
        ]);

        $file = $request->file('bill_file');
        $billType = $request->bill_type;
        
        // Store the uploaded file on the private disk — bills contain sensitive
        // consumption/cost data and must not be web-accessible. Served later via
        // the authorized utility.download route.
        $path = $file->store('utility_bills', 'local');
        $filePath = Storage::disk('local')->path($path);
        $ext = strtolower($file->getClientOriginalExtension());

        // Step 1: Extract text using OCR
        $text = '';
        $ocrMethod = 'unknown';
        $ocrRawResponse = null;

        // Try OCR.space API first if API key is configured
        $ocrSpaceApiKey = config('services.ocr_space.key');
        if (!empty($ocrSpaceApiKey)) {
            try {
                $response = Http::withHeaders([
                    'apikey' => $ocrSpaceApiKey
                ])->timeout(20)->attach(
                    'file', file_get_contents($filePath), $file->getClientOriginalName()
                )->post('https://api.ocr.space/parse/image', [
                    'language' => 'eng',
                    'isOverlayRequired' => 'false',
                ]);

                $result = $response->json();
                $ocrRawResponse = $result;

                if (isset($result['ParsedResults'][0]['ParsedText'])) {
                    $text = $result['ParsedResults'][0]['ParsedText'];
                    $ocrMethod = 'ocr_space';
                } elseif (isset($result['ErrorMessage'])) {
                    // Log error but continue to fallback
                    Log::warning('OCR.space API error: ' . $result['ErrorMessage']);
                }
            } catch (\Exception $e) {
                // Log error but continue to fallback
                Log::warning('OCR.space API exception: ' . $e->getMessage());
            }
        }

        // Fallback to TesseractOCR if OCR.space failed or is not configured
        if (empty($text)) {
            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                try {
                    // Try to get Tesseract path from config or use default
                    $tesseractPath = config('services.tesseract.path', 'tesseract');
                    
                    $text = (new TesseractOCR($filePath))
                        ->executable($tesseractPath)
                        ->run();
                    $ocrMethod = 'tesseract';
                } catch (\Exception $e) {
                    return back()->withInput()->with('error', 'OCR extraction failed: ' . $e->getMessage());
                }
            } elseif ($ext === 'pdf') {
                try {
                    // Try to extract text directly using pdftotext (if available).
                    // escapeshellarg() guards the interpolated path.
                    $text = shell_exec('pdftotext ' . escapeshellarg($filePath) . ' - 2>&1');
                    
                    if (!empty($text)) {
                        $ocrMethod = 'pdftotext';
                    }
                    
                    // If pdftotext is not available, try using PDF to Image library
                    if (empty($text) && class_exists(\Spatie\PdfToImage\Pdf::class)) {
                        try {
                            $pdf = new \Spatie\PdfToImage\Pdf($filePath);
                            $pdf->setPage(1);
                            
                            // Create temp directory if it doesn't exist
                            $tempDir = storage_path('app/temp');
                            if (!file_exists($tempDir)) {
                                mkdir($tempDir, 0755, true);
                            }
                            
                            $imagePath = $tempDir . '/' . uniqid() . '.png';
                            $pdf->saveImage($imagePath);

                            try {
                                $tesseractPath = config('services.tesseract.path', 'tesseract');
                                $text = (new TesseractOCR($imagePath))
                                    ->executable($tesseractPath)
                                    ->run();
                                $ocrMethod = 'tesseract_pdf';
                            } finally {
                                // Always clean up the temp image, even if OCR throws.
                                if (file_exists($imagePath)) {
                                    unlink($imagePath);
                                }
                            }
                        } catch (\Exception $pdfException) {
                            // If PDF processing fails, return error
                            return back()->withInput()->with('error', 'PDF processing failed. Please convert PDF to image (JPG/PNG) or install pdftotext utility.');
                        }
                    }
                    
                    if (empty($text)) {
                        return back()->withInput()->with('error', 'Could not extract text from PDF. Please convert PDF to image (JPG/PNG) or install pdftotext utility.');
                    }
                } catch (\Exception $e) {
                    return back()->withInput()->with('error', 'PDF processing failed: ' . $e->getMessage() . '. Please ensure spatie/pdf-to-image is installed or convert PDF to image first.');
                }
            } else {
                return back()->withInput()->with('error', 'Unsupported file type.');
            }
        }

        if (empty($text)) {
            return back()->withInput()->with('error', 'Could not extract text from the bill. Please ensure the image is clear.');
        }

        // Step 2: Extract structured data based on bill type
        if ($billType === 'electricity') {
            $extractedData = $this->extractor->extractElectricityData($text);
        } else {
            $extractedData = $this->extractor->extractFuelData($text);
        }

        // Step 3: Get facility and department
        $facility = Facilities::findOrFail($request->facility_id);
        $department = $request->department_id ? Department::find($request->department_id) : null;

        // Step 4: Determine scope and emission source
        $scope = $billType === 'electricity' ? 2 : 1; // Scope 2 for electricity, Scope 1 for fuel
        $emissionSourceName = $request->emission_source;

        // Step 5: Get emission factor
        $emissionSource = EmissionSource::where('name', 'like', '%' . $emissionSourceName . '%')
            ->orWhere('name', 'like', '%' . ($billType === 'electricity' ? 'Electricity' : 'Fuel') . '%')
            ->first();

        $emissionFactor = null;
        $factorValue = 0;
        $consumptionUnit = $extractedData['consumption_unit'] ?? null;

        if ($emissionSource) {
            $emissionFactor = EmissionFactor::where('emission_source_id', $emissionSource->id)
                ->where('unit', $consumptionUnit ?? 'kWh')
                ->first();

            if ($emissionFactor) {
                $factorValue = $emissionFactor->factor_value;
            }
        }

        // The expected unit for the default factors below. A default factor is only
        // valid if the extracted unit matches it — otherwise applying it produces a
        // silently wrong CO2e (e.g. a "MWh" reading times a per-kWh factor).
        $expectedUnit = $billType === 'electricity' ? ['kwh'] : ['l', 'liter', 'liters', 'litre', 'litres'];
        $unitMismatch = false;

        // Default emission factors if not found in database
        if ($factorValue == 0) {
            $normalizedUnit = $consumptionUnit ? strtolower(trim($consumptionUnit)) : null;
            if ($normalizedUnit !== null && !in_array($normalizedUnit, $expectedUnit, true)) {
                $unitMismatch = true;
            }

            if ($billType === 'electricity') {
                $factorValue = 0.527 / 1000; // Default: kg CO2e per kWh → tCO2e
            } else {
                // For fuel (diesel/gasoline) - default: 2.68 kg CO2e per liter
                $factorValue = 2.68 / 1000; // → tCO2e
            }
        }

        // Step 6: Calculate CO2e (skip when the unit can't be trusted with the factor)
        $co2eValue = 0;
        if ($extractedData['consumption'] && $factorValue > 0 && !$unitMismatch) {
            $co2eValue = $extractedData['consumption'] * $factorValue;
        }

        // Step 7: Save utility bill
        // Add OCR method to extracted data for tracking
        $extractedData['ocr_method'] = $ocrMethod;

        $companyId = current_company_id() ?? Auth::user()?->company_id;

        // De-duplication: don't re-process an identical bill (same company, type,
        // date, consumption, cost) — it would double-count emissions.
        $duplicate = UtilityBill::where('company_id', $companyId)
            ->where('bill_type', $billType)
            ->where('bill_date', $extractedData['bill_date'])
            ->where('consumption', $extractedData['consumption'])
            ->where('cost', $extractedData['cost'])
            ->when($extractedData['bill_date'], fn ($q) => $q, fn ($q) => $q->whereRaw('1 = 0'))
            ->first();

        if ($duplicate) {
            return redirect()->route('utility.index')
                ->with('warning', 'This bill looks identical to one already uploaded (same date, consumption and amount), so it was not processed again to avoid double-counting.');
        }

        $bill = UtilityBill::create([
            'company_id' => $companyId,
            'site_id' => null,
            'file_path' => $path,
            'bill_type' => $billType,
            'supplier_name' => $extractedData['supplier_name'],
            'bill_date' => $extractedData['bill_date'],
            'consumption' => $extractedData['consumption'],
            'consumption_unit' => $extractedData['consumption_unit'],
            'cost' => $extractedData['cost'],
            'raw_text' => $text,
            'raw_response' => $ocrRawResponse ? json_encode($ocrRawResponse) : null,
            'extracted_data' => $extractedData,
            'created_by' => Auth::id(),
        ]);

        // Step 8: Create emission record only when we have enough trustworthy data.
        $warnings = [];
        $recordCreated = false;

        if ($extractedData['consumption'] && $extractedData['bill_date'] && !$unitMismatch) {
            $emissionRecord = EmissionRecord::create([
                'entry_date' => $extractedData['bill_date'],
                'company_id' => $companyId ?? $bill->company_id ?? null,
                'facility' => $facility->name,
                'scope' => $scope,
                'emission_source' => $emissionSourceName,
                'activity_data' => $extractedData['consumption'],
                'emission_factor' => $factorValue,
                'co2e_value' => $co2eValue,
                'confidence_level' => $extractedData['confidence'] ?? 'medium',
                'department' => $department ? $department->name : null,
                'data_source' => 'api',
                'notes' => "Extracted from {$billType} bill via OCR. Supplier: " . ($extractedData['supplier_name'] ?? 'Unknown'),
                'created_by' => Auth::id(),
                'status' => 'draft', // Set as draft for review
            ]);

            // Link emission record to bill
            $bill->update(['emission_record_id' => $emissionRecord->id]);
            $recordCreated = true;
        } else {
            if (!$extractedData['consumption']) {
                $warnings[] = 'consumption could not be read';
            }
            if (!$extractedData['bill_date']) {
                $warnings[] = 'the bill date could not be read';
            }
            if ($unitMismatch) {
                $warnings[] = "the extracted unit ({$consumptionUnit}) does not match the expected unit for {$billType}";
            }
        }

        if ($recordCreated) {
            return redirect()->route('utility.index')
                ->with('success', 'Bill uploaded and processed successfully! ' .
                      ($extractedData['consumption'] ? "Consumption: {$extractedData['consumption']} {$extractedData['consumption_unit']}, CO2e: " . number_format($co2eValue, 4) . " tCO₂e" : 'Please review extracted data.'));
        }

        return redirect()->route('utility.index')
            ->with('warning', 'Bill uploaded, but no emission record was created because ' . implode(' and ', $warnings) . '. Please review the bill and add the emission record manually.');
    }
}
