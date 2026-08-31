<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProjectPerformanceExport implements FromArray, WithHeadings
{
    /**
     * @param  array<int, array{name: string, owner: string, tasks_count: int, completed_tasks_count: int, overdue_tasks_count: int}>  $rows
     */
    public function __construct(private readonly array $rows) {}

    public function headings(): array
    {
        return ['Project', 'Owner', 'Total Tasks', 'Completion Rate', 'On-Time Rate', 'Overdue'];
    }

    public function array(): array
    {
        return array_map(function (array $row): array {
            $total = $row['tasks_count'];

            return [
                $row['name'],
                $row['owner'],
                $total,
                $total === 0 ? '0%' : round($row['completed_tasks_count'] / $total * 100, 2).'%',
                $total === 0 ? '0%' : round(($total - $row['overdue_tasks_count']) / $total * 100, 2).'%',
                $row['overdue_tasks_count'],
            ];
        }, $this->rows);
    }
}
