<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TeamWorkloadExport implements FromArray, WithHeadings
{
    /**
     * @param  array<int, array{name: string, open_tasks_count: int, overdue_tasks_count: int, urgent_tasks_count: int}>  $rows
     */
    public function __construct(private readonly array $rows) {}

    public function headings(): array
    {
        return ['Team Member', 'Open Tasks', 'Overdue', 'Urgent / High Priority'];
    }

    public function array(): array
    {
        return array_map(fn (array $row): array => [
            $row['name'],
            $row['open_tasks_count'],
            $row['overdue_tasks_count'],
            $row['urgent_tasks_count'],
        ], $this->rows);
    }
}
