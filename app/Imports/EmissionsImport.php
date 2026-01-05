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

        /**
         * ---------------------------------------------------
         * SAFE MAPPING (NO UNDEFINED ARRAY KEY ERRORS)
         * ---------------------------------------------------
         */

        $facilityColumn   = $this->mapping['facility_id'] ?? $this->mapping['facility'] ?? null;
        $departmentColumn = $this->mapping['department_id'] ?? $this->mapping['department'] ?? null;
        $dateColumn       = $this->mapping['entry_date'] ?? $this->mapping['date'] ?? null;

        if (!$facilityColumn || !$departmentColumn || !$dateColumn) {
            // Mapping itself is invalid
            $this->skippedCount++;
            Log::warning('Import: Missing required mapping', ['mapping' => $this->mapping, 'row' => $row]);
            return null;
        }

        // Normalize column names to match Laravel Excel's WithHeadingRow normalization
        $facilityColumnNormalized = $this->normalizeColumnName($facilityColumn);
        $departmentColumnNormalized = $this->normalizeColumnName($departmentColumn);
        $dateColumnNormalized = $this->normalizeColumnName($dateColumn);

        // Try both normalized and original column names
        $facilityName   = $row[$facilityColumnNormalized] ?? $row[$facilityColumn] ?? null;
        $departmentName = $row[$departmentColumnNormalized] ?? $row[$departmentColumn] ?? null;
        $dateValue      = $row[$dateColumnNormalized] ?? $row[$dateColumn] ?? null;

        if (empty($facilityName) || empty($departmentName)) {
            $this->skippedCount++;
            Log::debug('Import: Empty facility or department', [
                'facility' => $facilityName,
                'department' => $departmentName,
                'row_keys' => array_keys($row)
            ]);
            return null;
        }

        /**
         * ---------------------------------------------------
         * RESOLVE FACILITY & DEPARTMENT
         * Support both ID (numeric) and Name (string) lookups
         * ---------------------------------------------------
         */

        // Check if facility value is numeric (ID) or string (name)
        if (is_numeric($facilityName)) {
            $facility = Facilities::find((int)$facilityName);
        } else {
            $facility = Facilities::where('name', trim($facilityName))->first();
        }

        // Check if department value is numeric (ID) or string (name)
        if (is_numeric($departmentName)) {
            $department = Department::find((int)$departmentName);
        } else {
            $department = Department::where('name', trim($departmentName))->first();
        }

        if (!$facility || !$department) {
            $this->skippedCount++;
            Log::warning('Import: Facility or Department not found', [
                'facility_value' => $facilityName,
                'department_value' => $departmentName,
                'facility_found' => $facility !== null,
                'department_found' => $department !== null,
                'facility_type' => is_numeric($facilityName) ? 'ID' : 'Name',
                'department_type' => is_numeric($departmentName) ? 'ID' : 'Name'
            ]);
            return null;
        }

        /**
         * ---------------------------------------------------
         * PREPARE DATA
         * ---------------------------------------------------
         */

        // Helper function to get value from row with normalization
        $getRowValue = function($mappingKey) use ($row) {
            $column = $this->mapping[$mappingKey] ?? null;
            if (!$column) return null;
            $normalized = $this->normalizeColumnName($column);
            return $row[$normalized] ?? $row[$column] ?? null;
        };

        $data = [
            'entry_date'       => $dateValue,
            'facility'         => $facility->name, // Store as string name, not ID
            'department'       => $department->name, // Store as string name, not ID
            'scope'            => $getRowValue('scope'),
            'emission_source'  => $getRowValue('emission_source'),
            'activity_data'    => $getRowValue('activity_data'),
            'emission_factor'  => $getRowValue('emission_factor'),
            'co2e_value'       => $getRowValue('co2e_value'),
            'confidence_level' => $getRowValue('confidence_level') ?: 'medium',
            'data_source'      => 'import',
            'notes'            => $getRowValue('notes'),
            'created_by'       => auth()->id(),
        ];

        
        /**
         * ---------------------------------------------------
         * INSERT OR UPDATE
         * ---------------------------------------------------
         */

        if ($this->overwrite) {
            EmissionRecord::updateOrCreate(
                [
                    'entry_date'      => $data['entry_date'],
                    'facility'        => $data['facility'],
                    'department'      => $data['department'],
                    'emission_source' => $data['emission_source'],
                ],
                $data
            );

            return null; // REQUIRED when using updateOrCreate
        }

        return new EmissionRecord($data);
    }
}
