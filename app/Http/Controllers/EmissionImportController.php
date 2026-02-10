<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Imports\EmissionsImport;
use App\Models\ImportHistory;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Carbon\Carbon;

class EmissionImportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:list-emissions-import|create-emissions-import|edit-emissions-import|delete-emissions-import', ['only' => ['showImportForm', 'downloadSample']]);
        $this->middleware('permission:create-emissions-import', ['only' => ['import']]);
    }

    public function showImportForm()
    {
        return view('emissions.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file'     => 'required|mimes:csv,xlsx,xls|max:10240',
            'mapping'  => 'required|json',
            'overwrite'=> 'nullable|boolean',
        ]);

        $mapping = json_decode($request->mapping, true);
        if (!is_array($mapping)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid mapping data',
            ], 422);
        }

        $companyId = function_exists('current_company_id') ? current_company_id() : (auth()->user()->company_id ?? null);
        if (!$companyId) {
            return response()->json([
                'status' => 'error',
                'message' => 'No company selected. Please select a company before importing.',
            ], 422);
        }

        $hasDate = !empty($mapping['entry_date']) || !empty($mapping['date']);
        $hasFacility = !empty($mapping['facility']) || !empty($mapping['facility_id']);
        $hasDepartment = !empty($mapping['department']) || !empty($mapping['department_id']);
        if (!$hasDate || !$hasFacility || !$hasDepartment) {
            return response()->json([
                'status' => 'error',
                'message' => 'Required columns must be mapped: Entry Date, Facility, and Department.',
            ], 422);
        }

        $overwrite = (bool) $request->overwrite;
        $file = $request->file('file');

        // Create import history record
        $importHistory = ImportHistory::create([
            'import_id' => ImportHistory::generateImportId(),
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'import_type' => in_array(strtolower($file->getClientOriginalExtension()), ['xlsx', 'xls']) ? 'excel' : 'csv',
            'status' => 'processing',
            'metadata' => [
                'mapping' => $mapping,
                'overwrite' => $overwrite,
                'description' => 'Emission data import',
            ],
            'user_id' => auth()->id(),
            'started_at' => Carbon::now(),
        ]);
        
        // Store file for later download if needed
        $filePath = $file->store('imports', 'public');
        $importHistory->update(['file_path' => $filePath]);

        try {
            $importClass = new EmissionsImport($overwrite, $mapping);
            $importClass->setImportHistoryId($importHistory->id);
            
            Excel::import($importClass, $file);
            
            // Get statistics from import class
            $processedCount = $importClass->getProcessedCount();
            $skippedCount = $importClass->getSkippedCount();
            $successfulCount = $processedCount - $skippedCount;
            
            $processingTime = Carbon::now()->diffInSeconds($importHistory->started_at);
            
            // Update import history with success status
            $importHistory->update([
                'status' => $skippedCount > 0 && $successfulCount > 0 ? 'partial' : 'completed',
                'total_records' => $processedCount,
                'successful_records' => $successfulCount,
                'failed_records' => $skippedCount,
                'processing_time' => $processingTime,
                'completed_at' => Carbon::now(),
                'logs' => json_encode([
                    ['level' => 'info', 'time' => Carbon::now()->format('H:i:s'), 'message' => 'Import started'],
                    ['level' => 'info', 'time' => Carbon::now()->format('H:i:s'), 'message' => "Parsed {$processedCount} records"],
                    ['level' => $skippedCount > 0 ? 'warning' : 'success', 'time' => Carbon::now()->format('H:i:s'), 'message' => "Import completed. {$successfulCount} successful, {$skippedCount} failed"],
                ]),
            ]);

            $message = $successfulCount > 0
                ? "Import completed. {$successfulCount} record(s) imported successfully" . ($skippedCount > 0 ? ", {$skippedCount} row(s) skipped." : ".")
                : 'No records were imported. Please check your data and mapping.';

            return response()->json([
                'status'    => 'success',
                'message'   => $message,
                'import_id' => $importHistory->import_id,
                'successful' => $successfulCount,
                'skipped'   => $skippedCount,
                'total'     => $processedCount,
            ]);
        } catch (\Throwable $e) {
            // Update import history with failure status
            $processingTime = Carbon::now()->diffInSeconds($importHistory->started_at);
            $importHistory->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'processing_time' => $processingTime,
                'completed_at' => Carbon::now(),
                'logs' => json_encode([
                    ['level' => 'info', 'time' => Carbon::now()->format('H:i:s'), 'message' => 'Import started'],
                    ['level' => 'error', 'time' => Carbon::now()->format('H:i:s'), 'message' => 'Import failed: ' . $e->getMessage()],
                ]),
            ]);
            
            return response()->json([
                'status'  => 'error',
                'message' => 'Import failed',
                'error'   => $e->getMessage(),
                'import_id' => $importHistory->import_id,
            ], 500);
        }
    }

    public function downloadSample()
    {
        $fileName = 'emission_sample.xlsx';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // ✅ Column names must match IMPORT EXPECTATION
        $columns = [
            'entry_date',
            'facility',
            'department',
            'scope',
            'emission_source',
            'activity_data',
            'emission_factor',
            'co2e_value',
            'confidence_level',
            'notes'
        ];

        // Header row
        foreach ($columns as $index => $column) {
            $sheet->setCellValue(chr(65 + $index) . '1', $column);
        }

        // Example rows (NAMES not IDs)
        $exampleData = [
            ['2025-12-31', 'Dubai Office', 'Operations', 2, 'Electricity', 1200, 0.527, 632.4, 'high', 'Monthly electricity'],
            ['2025-12-31', 'Abu Dhabi Plant', 'Production', 1, 'Natural Gas', 500, 2.0, 1000, 'medium', 'Gas usage'],
            ['2025-12-31', 'Sharjah Warehouse', 'Logistics', 3, 'Diesel', 300, 2.68, 804, 'low', 'Generator usage'],
        ];

        $row = 2;
        foreach ($exampleData as $data) {
            foreach ($data as $col => $value) {
                $sheet->setCellValue(chr(65 + $col) . $row, $value);
            }
            $row++;
        }

        // Auto-size
        foreach (range('A', chr(64 + count($columns))) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'emission_');
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
}
