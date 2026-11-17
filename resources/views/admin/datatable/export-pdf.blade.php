<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: dejavusans, sans-serif; font-size: 10px; color: #1f2937; }
        h1 { margin-bottom: 16px; font-size: 18px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 6px; border: 1px solid #d1d5db; text-align: left; }
        th { background: #f3f4f6; }
        tr:nth-child(even) { background: #f9fafb; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <table>
        <thead>
            <tr>
                @foreach ($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
