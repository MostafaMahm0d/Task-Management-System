<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OverdueTasksExport implements FromArray, WithHeadings
{
    /**
     * @param  array<int, array{title: string, project: string, assignee: string, due_date: string, days_overdue: int, priority: string, status: string}>  $rows
     */
    public function __construct(private readonly array $rows) {}

    public function headings(): array
    {
        return ['Task', 'Project', 'Assignee', 'Due Date', 'Days Overdue', 'Priority', 'Status'];
    }

    public function array(): array
    {
        return array_map(fn (array $row): array => [
            $row['title'],
            $row['project'],
            $row['assignee'],
            $row['due_date'],
            $row['days_overdue'],
            $row['priority'],
            $row['status'],
        ], $this->rows);
    }
}
