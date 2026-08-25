<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Excel/CSV export of the Scope 3 relevance screening — the table an assurer
 * asks for when checking that all 15 categories were considered and that every
 * exclusion carries a reason.
 *
 * Built from the same BoundaryStatementService output as the PDF, so the two
 * can never disagree.
 */
class BoundaryStatementExport implements FromArray, WithHeadings, WithTitle
{
    /**
     * @param  array<int, array<int, string>>  $rows
     */
    public function __construct(
        protected array $rows,
        protected int $year
    ) {}

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return ['Category', 'Name', 'Relevance', 'Justification', 'Sources included'];
    }

    public function title(): string
    {
        return 'Scope 3 screening '.$this->year;
    }
}
