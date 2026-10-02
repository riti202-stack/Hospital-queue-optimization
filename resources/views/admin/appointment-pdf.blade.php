<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 13px; color: #222; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #0f6e56; padding-bottom: 10px; }
        .header h1 { font-size: 18px; margin: 0; color: #0f6e56; }
        .header p { margin: 4px 0 0; color: #555; }
        .meta { margin-bottom: 15px; }
        .meta td { padding: 3px 10px 3px 0; }
        table.appointments { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.appointments th, table.appointments td {
            border: 1px solid #ccc; padding: 8px; text-align: left; font-size: 12px;
        }
        table.appointments th { background: #e8f5f1; }
        .footer { margin-top: 30px; font-size: 10px; color: #888; text-align: center; }
        .status { padding: 2px 8px; border-radius: 4px; font-size: 10px; }
        .status-booked { background: #e0f0ff; color: #05508a; }
        .status-completed { background: #e0f7e9; color: #0a6b2f; }
        .status-cancelled { background: #f4e0e0; color: #8a0505; }
        .status-no-show { background: #fdeedd; color: #8a4a05; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Hospital Queue Optimization System</h1>
        <p>Daily Appointment List</p>
    </div>

    <table class="meta">
        <tr><td><strong>Doctor:</strong></td><td>{{ $doctor->user->name }}</td></tr>
        <tr><td><strong>Department:</strong></td><td>{{ $doctor->department->name }}</td></tr>
        <tr><td><strong>Date:</strong></td><td>{{ $date->format('l, F j, Y') }}</td></tr>
        <tr><td><strong>Total appointments:</strong></td><td>{{ $appointments->count() }}</td></tr>
    </table>

    <table class="appointments">
        <thead>
            <tr>
                <th style="width:15%;">Time</th>
                <th style="width:40%;">Patient</th>
                <th style="width:25%;">Contact</th>
                <th style="width:20%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($appointments as $a)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($a->scheduled_time)->format('h:i A') }}</td>
                    <td>{{ $a->patient->name }}</td>
                    <td>{{ $a->patient->email }}</td>
                    <td><span class="status status-{{ $a->status }}">{{ ucfirst($a->status) }}</span></td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align:center; color:#888;">No appointments scheduled for this day.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generated on {{ now()->format('F j, Y \a\t h:i A') }} — Hospital Queue Optimization System
    </div>
</body>
</html>