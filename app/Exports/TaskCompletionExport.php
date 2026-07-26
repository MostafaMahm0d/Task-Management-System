<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TaskCompletionExport implements FromArray, WithHeadings
{
    /**
     * @param  array<int, array{name: string, tasks_count: int, completed_tasks_count: int, overdue_tasks_count: int}>  $rows
     */
    public function __construct(private readonly array $rows) {}

    public function headings(): array
    {
        return ['Project', 'Total Tasks', 'Completed', 'Completion Rate', 'Overdue'];
    }

    public function array(): array
    {
        return array_map(fn (array $row): array => [
            $row['name'],
            $row['tasks_count'],
            $row['completed_tasks_count'],
            $row['tasks_count'] === 0 ? '0%' : round($row['completed_tasks_count'] / $row['tasks_count'] * 100, 2).'%',
            $row['overdue_tasks_count'],
        ], $this->rows);
    }
}
