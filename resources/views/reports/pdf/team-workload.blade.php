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
    <h1>Team Workload Report</h1>
    <p>Generated {{ now()->format('M j, Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Team Member</th>
                <th>Open Tasks</th>
                <th>Overdue</th>
                <th>Urgent / High Priority</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['open_tasks_count'] }}</td>
                    <td>{{ $row['overdue_tasks_count'] }}</td>
                    <td>{{ $row['urgent_tasks_count'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
