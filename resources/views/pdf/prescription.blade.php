<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $rx->code() }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        .head { border-bottom: 2px solid #0f6e56; padding-bottom: 8px; margin-bottom: 12px; }
        h2 { margin: 0; color: #0f6e56; }
        table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 3px 0; vertical-align: top; }
        .meds th, .meds td { border: 1px solid #c9d6d3; padding: 6px; text-align: left; }
        .meds th { background: #e3f1ee; }
        .rx { font-size: 26px; color: #0f6e56; font-weight: bold; }
        .muted { color: #666; }
        .box { margin-top: 10px; }
        .sign { margin-top: 60px; text-align: right; }
        .sign span { border-top: 1px solid #333; padding-top: 4px; }
    </style>
</head>
<body>
    <div class="head">
        <table>
            <tr>
                <td>
                    <h2>{{ config('app.name') }}</h2>
                    <span class="muted">Outpatient Prescription</span>
                </td>
                <td style="text-align:right">
                    <strong>{{ $rx->code() }}</strong><br>
                    {{ $rx->created_at->format('d M Y, g:i A') }}
                </td>
            </tr>
        </table>
    </div>

    <table class="meta">
        <tr>
            <td><strong>Patient:</strong> {{ $rx->patientLabel() }}</td>
            <td>
                @if($rx->dependent)
                    <strong>Age / Sex:</strong> {{ $rx->dependent->age() ?? '—' }} / {{ $rx->dependent->gender ?? '—' }}
                @else
                    <strong>Phone:</strong> {{ $rx->patient->phone ?? '—' }}
                @endif
            </td>
        </tr>
        <tr>
            <td><strong>Doctor:</strong> {{ $rx->doctor?->displayName() }}</td>
            <td><strong>Department:</strong> {{ $rx->doctor?->department?->name ?? '—' }}</td>
        </tr>
    </table>

    @if($rx->symptoms)<div class="box"><strong>Symptoms:</strong> {{ $rx->symptoms }}</div>@endif
    <div class="box"><strong>Diagnosis:</strong> {{ $rx->diagnosis }}</div>

    <div class="box rx">&#8478;</div>
    @if($rx->items->isNotEmpty())
        <table class="meds">
            <thead><tr><th>#</th><th>Medicine</th><th>Dosage</th><th>Duration</th><th>Instruction</th></tr></thead>
            <tbody>
                @foreach($rx->items as $i => $it)
                    <tr><td>{{ $i + 1 }}</td><td>{{ $it->medicine }}</td><td>{{ $it->dosage }}</td><td>{{ $it->duration }}</td><td>{{ $it->instruction ?? '—' }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="muted">No medicines prescribed.</p>
    @endif

    @if($rx->tests_advised)<div class="box"><strong>Tests advised:</strong> {{ $rx->tests_advised }}</div>@endif
    @if($rx->advice)<div class="box"><strong>Advice:</strong> {{ $rx->advice }}</div>@endif
    @if($rx->follow_up_date)<div class="box"><strong>Follow-up:</strong> {{ $rx->follow_up_date->format('D, d M Y') }}</div>@endif

    <div class="sign"><span>{{ $rx->doctor?->displayName() }}</span></div>
</body>
</html>