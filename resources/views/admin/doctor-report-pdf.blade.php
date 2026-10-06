<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #222; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #0f6e56; padding-bottom: 10px; }
        .header h1 { font-size: 18px; margin: 0; color: #0f6e56; }
        .header p { margin: 4px 0 0; color: #555; }
        h2.section { font-size: 14px; color: #0f6e56; border-bottom: 1px solid #ccc; padding-bottom: 4px; margin-top: 25px; }
        table.list { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.list th, table.list td { border: 1px solid #ccc; padding: 6px; text-align: left; font-size: 11px; }
        table.list th { background: #e8f5f1; }
        .dept-name { font-size: 13px; font-weight: bold; margin-top: 15px; background: #f4f4f4; padding: 4px 8px; }
        .summary-grid { width: 100%; margin-top: 10px; }
        .summary-grid td { width: 25%; text-align: center; padding: 10px; border: 1px solid #ccc; }
        .summary-number { font-size: 20px; font-weight: bold; color: #0f6e56; }
        .summary-label { font-size: 10px; color: #666; }
        .status { padding: 2px 6px; border-radius: 4px; font-size: 9px; }
        .status-booked { background: #e0f0ff; color: #05508a; }
        .status-completed { background: #e0f7e9; color: #0a6b2f; }
        .status-cancelled { background: #f4e0e0; color: #8a0505; }
        .status-no-show { background: #fdeedd; color: #8a4a05; }
        .footer { margin-top: 30px; font-size: 9px; color: #888; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Hospital Queue Optimization System</h1>
        <p>Doctor Report — {{ $date->format('l, F j, Y') }}</p>
    </div>

    <h2 class="section">Doctor Directory</h2>
    @foreach($departments as $dept)
        <div class="dept-name">{{ $dept->name }} ({{ $dept->doctors->count() }} doctors)</div>
        @if($dept->doctors->isEmpty())
            <p style="color:#888; font-size:11px;">No doctors assigned.</p>
        @else
            <table class="list">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Room</th>
                        <th>Status</th>
                        @if($includeContact)
                            <th>Phone</th>
                            <th>License No.</th>
                            <th>Specialization</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($dept->doctors as $doc)
                        <tr>
                            <td>{{ $doc->user->name }}</td>
                            <td>{{ $doc->room_no ?? '—' }}</td>
                            <td>{{ $doc->is_available ? 'Available' : 'Unavailable' }}</td>
                            @if($includeContact)
                                <td>{{ $doc->personal_phone ?? '—' }}</td>
                                <td>{{ $doc->license_number ?? '—' }}</td>
                                <td>{{ $doc->specialization ?? '—' }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach

    @if($includeActivity && $activitySummary)
        <h2 class="section">Daily Activity Summary — {{ $date->format('M j, Y') }}</h2>
        <table class="summary-grid">
            <tr>
                <td><div class="summary-number">{{ $activitySummary['marked_available'] }}</div><div class="summary-label">Marked Available Today</div></td>
                <td><div class="summary-number">{{ $activitySummary['morning_active_doctors'] }}</div><div class="summary-label">Active Doctors — Morning</div></td>
                <td><div class="summary-number">{{ $activitySummary['afternoon_active_doctors'] }}</div><div class="summary-label">Active Doctors — Afternoon</div></td>
                <td><div class="summary-number">{{ $activitySummary['total_appointments'] }}</div><div class="summary-label">Total Appointments</div></td>
            </tr>
        </table>
        <p style="font-size:10px; color:#777; margin-top:8px;">
            "Active" doctors are those with at least one scheduled appointment in that time window (Morning: before 12:00 PM, Afternoon: 12:00 PM onward).
            Morning appointments: {{ $activitySummary['morning_appointments'] }} · Afternoon appointments: {{ $activitySummary['afternoon_appointments'] }}
        </p>
    @endif

    @if($includeAppointments)
        <h2 class="section">Appointments — {{ $date->format('M j, Y') }}</h2>
        @if($appointmentsToday->isEmpty())
            <p style="color:#888; font-size:11px;">No appointments scheduled for this date.</p>
        @else
            <table class="list">
                <thead>
                    <tr><th>Time</th><th>Patient</th><th>Doctor</th><th>Department</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @foreach($appointmentsToday as $a)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($a->scheduled_time)->format('h:i A') }}</td>
                            <td>{{ $a->patient->name }}</td>
                            <td>{{ $a->doctor->user->name ?? 'N/A' }}</td>
                            <td>{{ $a->department->name }}</td>
                            <td><span class="status status-{{ $a->status }}">{{ ucfirst($a->status) }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif

    <div class="footer">
        Generated on {{ now()->format('F j, Y \a\t h:i A') }} — Hospital Queue Optimization System
    </div>
</body>
</html>