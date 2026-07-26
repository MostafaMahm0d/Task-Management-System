<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        h1 { font-size: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background-color: #f3f4f6; }
    </style>
</head>
<body>
    <h1>Task Completion Report</h1>
    <p>Generated {{ now()->format('M j, Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Project</th>
                <th>Total Tasks</th>
                <th>Completed</th>
                <th>Completion Rate</th>
                <th>Overdue</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['tasks_count'] }}</td>
                    <td>{{ $row['completed_tasks_count'] }}</td>
                    <td>{{ $row['tasks_count'] === 0 ? '0%' : round($row['completed_tasks_count'] / $row['tasks_count'] * 100, 2) . '%' }}</td>
                    <td>{{ $row['overdue_tasks_count'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
