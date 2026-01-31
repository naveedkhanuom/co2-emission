<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\UtilityBill;
use App\Services\BillDataExtractor;
use thiagoalessio\TesseractOCR\TesseractOCR;

class BillOCRController extends Controller
{
    protected $extractor;

    public function __construct(BillDataExtractor $extractor)
    {
        $this->extractor = $extractor;
    }

    public function showForm()
    {
        return view('bill_upload');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'bill_file' => 'required|mimes:jpg,jpeg,png,pdf|max:10240',
            'bill_type' => 'required|in:electricity,fuel',
        ]);

        $file = $request->file('bill_file');
        $billType = $request->bill_type;
        
        // Store the uploaded file
        $path = $file->store('utility_bills', 'public');
        $filePath = storage_path('app/public/' . $path);
        $ext = strtolower($file->getClientOriginalExtension());

        // Step 1: Extract text using OCR
        $text = '';
        $ocrMethod = 'unknown';
        
        // Try OCR.space API first if API key is configured
        $ocrSpaceApiKey = env('OCR_SPACE_API_KEY');
        if (!empty($ocrSpaceApiKey)) {
            try {
                $response = Http::withHeaders([
                    'apikey' => $ocrSpaceApiKey
                ])->attach(
                    'file', file_get_contents($file->getRealPath()), $file->getClientOriginalName()
                )->post('https://api.ocr.space/parse/image', [
                    'language' => 'eng',
                    'isOverlayRequired' => 'false',
                ]);

                $result = $response->json();

                if (isset($result['ParsedResults'][0]['ParsedText'])) {
                    $text = $result['ParsedResults'][0]['ParsedText'];
                    $ocrMethod = 'ocr_space';
                } elseif (isset($result['ErrorMessage'])) {
                    Log::warning('OCR.space API error: ' . $result['ErrorMessage']);
                }
            } catch (\Exception $e) {
                Log::warning('OCR.space API exception: ' . $e->getMessage());
            }
        }

        // Fallback to TesseractOCR if OCR.space failed or is not configured
        if (empty($text)) {
            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                try {
                    $tesseractPath = env('TESSERACT_PATH', 'tesseract');
                    
                    $text = (new TesseractOCR($filePath))
                        ->executable($tesseractPath)
                        ->run();
                    $ocrMethod = 'tesseract';
                } catch (\Exception $e) {
                    return back()->withInput()->with('error', 'OCR extraction failed: ' . $e->getMessage());
                }
            } elseif ($ext === 'pdf') {
                try {
                    // Try to extract text directly using pdftotext (if available)
                    $text = @shell_exec("pdftotext \"$filePath\" - 2>&1");
                    
                    if (!empty($text)) {
                        $ocrMethod = 'pdftotext';
                    }
                    
                    // If pdftotext is not available, try using PDF to Image library
                    if (empty($text) && class_exists(\Spatie\PdfToImage\Pdf::class)) {
                        try {
                            $pdf = new \Spatie\PdfToImage\Pdf($filePath);
                            $pdf->setPage(1);
                            
                            $tempDir = storage_path('app/temp');
                            if (!file_exists($tempDir)) {
                                mkdir($tempDir, 0755, true);
                            }
                            
                            $imagePath = $tempDir . '/' . uniqid() . '.png';
                            $pdf->saveImage($imagePath);
                            
                            $tesseractPath = env('TESSERACT_PATH', 'tesseract');
                            $text = (new TesseractOCR($imagePath))
                                ->executable($tesseractPath)
                                ->run();
                            $ocrMethod = 'tesseract_pdf';
                                
                            if (file_exists($imagePath)) {
                                unlink($imagePath);
                            }
                        } catch (\Exception $pdfException) {
                            return back()->withInput()->with('error', 'PDF processing failed. Please convert PDF to image (JPG/PNG) or install pdftotext utility.');
                        }
                    }
                    
                    if (empty($text)) {
                        return back()->withInput()->with('error', 'Could not extract text from PDF. Please convert PDF to image (JPG/PNG) or install pdftotext utility.');
                    }
                } catch (\Exception $e) {
                    return back()->withInput()->with('error', 'PDF processing failed: ' . $e->getMessage());
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

        // Step 3: Calculate CO2e (optional, for display)
        $co2eValue = 0;
        if ($extractedData['consumption']) {
            if ($billType === 'electricity') {
                $factorValue = 0.527 / 1000; // Default: kg CO2e per kWh converted to tCO2e
            } else {
                $factorValue = 2.68 / 1000; // Default: kg CO2e per liter converted to tCO2e
            }
            $co2eValue = $extractedData['consumption'] * $factorValue;
        }
        $extractedData['co2e_value'] = $co2eValue;

        // Step 4: Save utility bill
        $extractedData['ocr_method'] = $ocrMethod;
        
        $bill = UtilityBill::create([
            'company_id' => Auth::user()->company_id ?? null,
            'site_id' => null,
            'file_path' => $path,
            'bill_type' => $billType,
            'supplier_name' => $extractedData['supplier_name'],
            'bill_date' => $extractedData['bill_date'],
            'consumption' => $extractedData['consumption'],
            'consumption_unit' => $extractedData['consumption_unit'],
            'cost' => $extractedData['cost'],
            'raw_text' => $text,
            'extracted_data' => $extractedData,
            'created_by' => Auth::id(),
        ]);

        // Prepare success message
        $consumptionInfo = '';
        if ($extractedData['consumption']) {
            $unit = $extractedData['consumption_unit'] ?? ($billType === 'fuel' ? 'L' : 'kWh');
            $consumptionInfo = "Extracted: " . number_format($extractedData['consumption'], 2) . " {$unit}";
            if ($extractedData['consumption_unit'] === 'L' || $billType === 'fuel') {
                $consumptionInfo .= " of fuel";
            } else {
                $consumptionInfo .= " of electricity";
            }
        }

        return redirect()->back()
            ->with('success', 'Bill uploaded and processed successfully! ' . $consumptionInfo)
            ->with('bill', $bill)
            ->with('extracted_data', $extractedData);
    }
}
