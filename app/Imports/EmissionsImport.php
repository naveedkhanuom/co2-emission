<?php

namespace App\Imports;

use App\Models\EmissionRecord;
use App\Models\Facilities;
use App\Models\Department;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class EmissionsImport implements ToModel, WithHeadingRow
{
    protected bool $overwrite;
    protected array $mapping;
    protected int $processedCount = 0;
    protected int $skippedCount = 0;
    protected ?int $importHistoryId = null;

    // Per-import memoisation of resolved facilities/departments so a spreadsheet
    // with many rows sharing the same facility/department doesn't re-query (and
    // firstOrCreate) once per row — the main N+1 in large imports.
    protected array $facilityCache = [];
    protected array $departmentCache = [];

    public function __construct(bool $overwrite = false, array $mapping = [])
    {
        $this->overwrite = $overwrite;
        $this->mapping   = $mapping;
    }

    public function setImportHistoryId(int $id)
    {
        $this->importHistoryId = $id;
    }

    public function getProcessedCount(): int
    {
        return $this->processedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    /**
     * Resolve (and auto-create) a facility by id/name, memoised for the import.
     */
    protected function resolveFacility($facilityName, $companyId): Facilities
    {
        $key = mb_strtolower(trim((string) $facilityName));
        if (isset($this->facilityCache[$key])) {
            return $this->facilityCache[$key];
        }

        if (is_numeric($facilityName)) {
            $facility = Facilities::find((int) $facilityName);
        } else {
            $facility = Facilities::where('name', $facilityName)->first();
        }

        if (! $facility) {
            $facility = Facilities::firstOrCreate(
                ['company_id' => $companyId, 'name' => $facilityName],
                ['company_id' => $companyId, 'name' => $facilityName]
            );
        }

        return $this->facilityCache[$key] = $facility;
    }

    /**
     * Resolve (and auto-create) a department by id/name under a facility,
     * memoised for the import.
     */
    protected function resolveDepartment($departmentName, Facilities $facility, $companyId): Department
    {
        $key = $facility->id . '|' . mb_strtolower(trim((string) $departmentName));
        if (isset($this->departmentCache[$key])) {
            return $this->departmentCache[$key];
        }

        if (is_numeric($departmentName)) {
            $department = Department::find((int) $departmentName);
        } else {
            $department = Department::where('name', $departmentName)->first();
        }

        if (! $department) {
            $department = Department::firstOrCreate(
                ['company_id' => $companyId, 'facility_id' => $facility->id, 'name' => $departmentName],
                ['company_id' => $companyId, 'facility_id' => $facility->id, 'name' => $departmentName]
            );
        }

        return $this->departmentCache[$key] = $department;
    }

    /**
     * Normalize column name to match Laravel Excel's WithHeadingRow behavior
     * Converts to lowercase and replaces spaces/special chars with underscores
     */
    protected function normalizeColumnName(string $columnName): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim($columnName)));
    }

    public function model(array $row)
    {
        $this->processedCount++;

        $facilityColumn   = $this->mapping['facility_id'] ?? $this->mapping['facility'] ?? null;
        $departmentColumn = $this->mapping['department_id'] ?? $this->mapping['department'] ?? null;
        $dateColumn       = $this->mapping['entry_date'] ?? $this->mapping['date'] ?? null;

        if (!$facilityColumn || !$departmentColumn || !$dateColumn) {
            $this->skippedCount++;
            Log::warning('Import: Missing required mapping', ['mapping' => $this->mapping, 'row' => $row]);
            return null;
        }

        $facilityColumnNormalized = $this->normalizeColumnName($facilityColumn);
        $departmentColumnNormalized = $this->normalizeColumnName($departmentColumn);
        $dateColumnNormalized = $this->normalizeColumnName($dateColumn);

        $facilityRaw   = $row[$facilityColumnNormalized] ?? $row[$facilityColumn] ?? null;
        $departmentRaw = $row[$departmentColumnNormalized] ?? $row[$departmentColumn] ?? null;
        $dateRaw       = $row[$dateColumnNormalized] ?? $row[$dateColumn] ?? null;

        $facilityName   = is_string($facilityRaw) ? trim($facilityRaw) : (is_numeric($facilityRaw) ? (string) $facilityRaw : null);
        $departmentName = is_string($departmentRaw) ? trim($departmentRaw) : (is_numeric($departmentRaw) ? (string) $departmentRaw : null);
        $dateValue      = $dateRaw !== null && $dateRaw !== '' ? trim((string) $dateRaw) : null;

        // Skip empty rows (all key fields empty)
        if (($facilityName === '' || $facilityName === null) && ($departmentName === '' || $departmentName === null) && ($dateValue === '' || $dateValue === null)) {
            $this->skippedCount++;
            return null;
        }

        if (empty($facilityName) || empty($departmentName) || empty($dateValue)) {
            $this->skippedCount++;
            Log::debug('Import: Empty required field', [
                'facility' => $facilityName,
                'department' => $departmentName,
                'date' => $dateValue,
                'row_keys' => array_keys($row),
            ]);
            return null;
        }

        $companyId = function_exists('current_company_id') ? current_company_id() : (auth()->check() ? auth()->user()->company_id : null);
        if (!$companyId) {
            $this->skippedCount++;
            return null;
        }

        // Resolve facility and department (memoised per import — see caches above).
        $facility = $this->resolveFacility($facilityName, $companyId);
        $department = $this->resolveDepartment($departmentName, $facility, $companyId);

        try {
            $parsedDate = \Carbon\Carbon::parse($dateValue)->format('Y-m-d');
        } catch (\Throwable $e) {
            $this->skippedCount++;
            Log::debug('Import: Invalid date format', ['date' => $dateValue, 'row' => $row]);
            return null;
        }

        $getRowValue = function ($mappingKey) use ($row) {
            $column = $this->mapping[$mappingKey] ?? null;
            if (!$column) return null;
            $normalized = $this->normalizeColumnName($column);
            $val = $row[$normalized] ?? $row[$column] ?? null;
            return $val !== null && $val !== '' ? $val : null;
        };

        $scope = $getRowValue('scope');
        if ($scope !== null) {
            $scope = is_numeric($scope) ? (int) $scope : (in_array((string) $scope, ['1', '2', '3']) ? (int) $scope : 1);
        } else {
            $scope = 1;
        }
        $scope = in_array($scope, [1, 2, 3]) ? $scope : 1;

        $activityData = $getRowValue('activity_data');
        $activityData = $activityData !== null && is_numeric($activityData) ? (float) $activityData : null;

        $emissionFactor = $getRowValue('emission_factor');
        $emissionFactor = $emissionFactor !== null && is_numeric($emissionFactor) ? (float) $emissionFactor : null;

        $co2eValue = $getRowValue('co2e_value');
        $co2eValue = $co2eValue !== null && is_numeric($co2eValue) ? (float) $co2eValue : 0;

        $confidenceLevel = $getRowValue('confidence_level');
        $confidenceLevel = is_string($confidenceLevel) ? strtolower(trim($confidenceLevel)) : 'medium';
        $confidenceLevel = in_array($confidenceLevel, ['low', 'medium', 'high', 'estimated']) ? $confidenceLevel : 'medium';

        $data = [
            'company_id'       => $companyId,
            'entry_date'       => $parsedDate,
            'facility'         => $facility->name,
            'department'       => $department->name,
            'scope'            => $scope,
            'emission_source'  => $getRowValue('emission_source') ?? 'Imported',
            'activity_data'    => $activityData,
            'emission_factor'  => $emissionFactor,
            'co2e_value'       => $co2eValue,
            'confidence_level' => $confidenceLevel,
            'data_source'      => 'import',
            'notes'            => $getRowValue('notes'),
            'created_by'       => auth()->id(),
            'status'           => 'active',
        ];

        
        /**
         * ---------------------------------------------------
         * INSERT OR UPDATE
         * ---------------------------------------------------
         */

        if ($this->overwrite) {
            $match = [
                'company_id'      => $companyId,
                'entry_date'      => $data['entry_date'],
                'facility'        => $data['facility'],
                'department'      => $data['department'],
                'emission_source' => $data['emission_source'],
            ];
            EmissionRecord::updateOrCreate($match, $data);

            return null; // REQUIRED when using updateOrCreate
        }

        return new EmissionRecord($data);
    }
}
