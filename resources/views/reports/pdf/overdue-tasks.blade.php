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
    <h1>Overdue Tasks Report</h1>
    <p>Generated {{ now()->format('M j, Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Task</th>
                <th>Project</th>
                <th>Assignee</th>
                <th>Due Date</th>
                <th>Days Overdue</th>
                <th>Priority</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['title'] }}</td>
                    <td>{{ $row['project'] }}</td>
                    <td>{{ $row['assignee'] }}</td>
                    <td>{{ $row['due_date'] }}</td>
                    <td>{{ $row['days_overdue'] }}</td>
                    <td>{{ $row['priority'] }}</td>
                    <td>{{ $row['status'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
