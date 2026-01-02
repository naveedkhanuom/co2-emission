<?php

namespace App\Imports;

use App\Models\EmissionRecord;
use App\Models\Facilities;
use App\Models\Department;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EmissionsImport implements ToModel, WithHeadingRow
{
    protected bool $overwrite;
    protected array $mapping;

    public function __construct(bool $overwrite = false, array $mapping = [])
    {
        $this->overwrite = $overwrite;
        $this->mapping   = $mapping;
    }

    public function model(array $row)
    {
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
            return null;
        }

        $facilityName   = $row[$facilityColumn]   ?? null;
        $departmentName = $row[$departmentColumn] ?? null;

        if (empty($facilityName) || empty($departmentName)) {
            return null;
        }

        /**
         * ---------------------------------------------------
         * RESOLVE FACILITY & DEPARTMENT
         * ---------------------------------------------------
         */

        $facility = Facilities::where('name', trim($facilityName))->first();
        $department = Department::where('name', trim($departmentName))->first();

        if (!$facility || !$department) {
            return null;
        }

        /**
         * ---------------------------------------------------
         * PREPARE DATA
         * ---------------------------------------------------
         */

        $data = [
            'entry_date'       => $row[$dateColumn] ?? null,
            'facility'         => $facility->id,
            'department'       => $department->id,
            'scope'            => $row[$this->mapping['scope'] ?? ''] ?? null,
            'emission_source'  => $row[$this->mapping['emission_source'] ?? ''] ?? null,
            'activity_data'    => $row[$this->mapping['activity_data'] ?? ''] ?? null,
            'emission_factor'  => $row[$this->mapping['emission_factor'] ?? ''] ?? null,
            'co2e_value'       => $row[$this->mapping['co2e_value'] ?? ''] ?? null,
            'confidence_level' => $row[$this->mapping['confidence_level'] ?? ''] ?? 'medium',
            'data_source'      => 'import',
            'notes'            => $row[$this->mapping['notes'] ?? ''] ?? null,
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
