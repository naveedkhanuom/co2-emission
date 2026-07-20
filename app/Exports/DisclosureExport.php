<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Excel/CSV export of a single disclosure framework's datapoints, produced by
 * DisclosureReportService::datapoints() so the figures match the PDF exactly.
 */
class DisclosureExport implements FromArray, WithHeadings, WithTitle
{
    /**
     * @param array<int,array{ref:string,label:string,value:mixed,unit:string}> $datapoints
     */
    public function __construct(
        protected array $datapoints,
        protected string $frameworkLabel
    ) {
    }

    public function array(): array
    {
        return array_map(fn ($d) => [
            $d['ref'],
            $d['label'],
            is_numeric($d['value']) ? $d['value'] : (string) $d['value'],
            $d['unit'],
        ], $this->datapoints);
    }

    public function headings(): array
    {
        return ['Reference', 'Datapoint', 'Value', 'Unit'];
    }

    public function title(): string
    {
        return mb_substr(preg_replace('/[^A-Za-z0-9 ]/', '', $this->frameworkLabel), 0, 31) ?: 'Disclosure';
    }
}
