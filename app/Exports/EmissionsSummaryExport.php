<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Excel/CSV export of an emissions summary produced by ReportGenerationService.
 * Takes the already-built summary array so the figures match the PDF exactly.
 */
class EmissionsSummaryExport implements FromArray, WithHeadings, WithTitle
{
    public function __construct(protected array $summary)
    {
    }

    public function array(): array
    {
        $t = $this->summary['totals'];

        $rows = [
            ['Scope 1', '', $t['scope1']],
            ['Scope 2', '', $t['scope2']],
            ['Scope 3', '', $t['scope3']],
            ['TOTAL', '', $t['total']],
            ['', '', ''],
        ];

        foreach ($this->summary['by_source'] as $r) {
            $rows[] = [$r['source'], 'Scope ' . $r['scope'], $r['co2e']];
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['Source', 'Scope', 'tCO2e'];
    }

    public function title(): string
    {
        return 'Emissions Summary';
    }
}
