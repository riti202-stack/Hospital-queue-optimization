<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 28px 32px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
    .header { text-align: center; border-bottom: 2px solid #0f6e56; padding-bottom: 8px; margin-bottom: 16px; }
    .header h1 { font-size: 16px; margin: 0; color: #0f6e56; }
    .header p { margin: 3px 0 0; color: #666; font-size: 10px; }

    .profile { width: 100%; margin-bottom: 10px; }
    .profile td { vertical-align: middle; }
    .photo { width: 80px; height: 80px; border-radius: 40px; }
    .avatar { width: 80px; height: 80px; border-radius: 40px; background: #0f6e56; color: #fff;
              font-size: 34px; text-align: center; line-height: 80px; }
    .doc-name { font-size: 18px; font-weight: bold; margin: 0; }
    .doc-sub { color: #555; margin: 3px 0; }

    h2 { font-size: 13px; color: #0f6e56; border-bottom: 1px solid #ccc; padding-bottom: 3px; margin: 18px 0 6px; }
    table.kv { width: 100%; border-collapse: collapse; }
    table.kv td { padding: 5px 6px; border-bottom: 1px solid #eee; }
    table.kv td.k { width: 35%; color: #666; }

    table.list { width: 100%; border-collapse: collapse; margin-top: 6px; }
    table.list th, table.list td { border: 1px solid #ccc; padding: 5px; text-align: left; font-size: 10px; }
    table.list th { background: #e8f5f1; }

    .badge { padding: 2px 7px; border-radius: 4px; font-size: 9px; font-weight: bold; }
    .b-green { background: #e0f7e9; color: #0a6b2f; }
    .b-red { background: #f8e0e0; color: #8a0505; }
    .b-amber { background: #fdf1d6; color: #8a5a05; }
    .b-grey { background: #eee; color: #555; }
    .b-blue { background: #e0f0ff; color: #05508a; }

    .stats { width: 100%; border-collapse: collapse; margin-top: 6px; }
    .stats td { border: 1px solid #ccc; text-align: center; padding: 8px 4px; width: 14%; }
    .num { font-size: 17px; font-weight: bold; color: #0f6e56; }
    .lbl { font-size: 9px; color: #666; }

    .footer { margin-top: 24px; font-size: 9px; color: #888; text-align: center; }
</style>
</head>
<body>
@php
    $statusClass = fn($s) => match($s) {
        'completed' => 'b-green', 'cancelled' => 'b-red', 'no-show' => 'b-amber', default => 'b-blue'
    };
    $bg = match($doctor->background_check_status) { 'cleared' => 'b-green', 'flagged' => 'b-red', default => 'b-amber' };
    $lic = match($licenseStatus) { 'expired' => ['b-red','Expired'], 'expiring' => ['b-amber','Expires within 30 days'], 'valid' => ['b-green','Valid'], default => null };
    $dash = '—';
@endphp

<div class="header">
    <h1>Hospital Queue Optimization System</h1>
    <p>Doctor Profile Report — {{ $doctor->department->name ?? $dash }} Department</p>
</div>

<table class="profile">
    <tr>
        <td style="width:95px;">
            @if($photo)
                <img src="{{ $photo }}" class="photo">
            @else
                <div class="avatar">{{ strtoupper(substr(preg_replace('/^Dr\.?\s*/i', '', $doctor->user->name ?? 'D'), 0, 1)) }}</div>
            @endif
        </td>
        <td>
            <p class="doc-name">{{ $doctor->user->name ?? 'Doctor #'.$doctor->id }}</p>
            <p class="doc-sub">{{ $doctor->specialization ?? 'Specialization not set' }}{{ $doctor->sub_specialty ? ' · '.$doctor->sub_specialty : '' }}</p>
            <p class="doc-sub">{{ $doctor->department->name ?? $dash }} · Room {{ $doctor->room_no ?? $dash }} · Doctor ID #{{ $doctor->id }}</p>
            <span class="badge {{ $doctor->is_available ? 'b-green' : 'b-grey' }}">{{ $doctor->is_available ? 'Available' : 'Unavailable' }}</span>
        </td>
    </tr>
</table>

@if($includeContact)
<h2>Personal &amp; Contact</h2>
<table class="kv">
    <tr><td class="k">Date of birth</td><td>{{ $doctor->date_of_birth ?? $dash }}{{ $age ? ' ('.$age.' years)' : '' }}</td></tr>
    <tr><td class="k">Gender</td><td>{{ ucfirst($doctor->gender ?? $dash) }}</td></tr>
    <tr><td class="k">Hospital email</td><td>{{ $doctor->user->email ?? $dash }}</td></tr>
    <tr><td class="k">Personal phone</td><td>{{ $doctor->personal_phone ?? $dash }}</td></tr>
    <tr><td class="k">Residential address</td><td>{{ $doctor->residential_address ?? $dash }}</td></tr>
    <tr><td class="k">Emergency contact</td><td>{{ $doctor->emergency_contact_name ?? $dash }}{{ $doctor->emergency_contact_phone ? ' — '.$doctor->emergency_contact_phone : '' }}</td></tr>
</table>
@endif

<h2>Licensure &amp; Specialization</h2>
<table class="kv">
    <tr><td class="k">License number</td><td>{{ $doctor->license_number ?? $dash }}</td></tr>
    <tr><td class="k">Issuing body</td><td>{{ $doctor->license_issuing_body ?? $dash }}</td></tr>
    <tr><td class="k">License expiry</td><td>{{ $doctor->license_expiry ?? $dash }} @if($lic)<span class="badge {{ $lic[0] }}">{{ $lic[1] }}</span>@endif</td></tr>
    <tr><td class="k">Specialization</td><td>{{ $doctor->specialization ?? $dash }}</td></tr>
    <tr><td class="k">Sub-specialty</td><td>{{ $doctor->sub_specialty ?? $dash }}</td></tr>
</table>

<h2>Qualifications</h2>
@if($doctor->qualifications->isEmpty())
    <p style="color:#888;">No qualifications recorded.</p>
@else
    <table class="list">
        <thead><tr><th>Degree</th><th>Institution</th><th>Passing year</th></tr></thead>
        <tbody>
        @foreach($doctor->qualifications->sortBy('passing_year') as $q)
            <tr><td>{{ $q->degree }}</td><td>{{ $q->institution }}</td><td>{{ $q->passing_year }}</td></tr>
        @endforeach
        </tbody>
    </table>
@endif

<h2>Employment &amp; Operational</h2>
<table class="kv">
    <tr><td class="k">Employment type</td><td>{{ $doctor->employment_type ? ucfirst(str_replace('_', ' ', $doctor->employment_type)) : $dash }}</td></tr>
    <tr><td class="k">Date of joining</td><td>{{ $doctor->date_of_joining ?? $dash }}</td></tr>
    <tr><td class="k">Working hours / OPD days</td><td>{{ $doctor->working_hours ?? $dash }}</td></tr>
    <tr><td class="k">Fee share</td><td>{{ $doctor->fee_share_percent ? $doctor->fee_share_percent.'%' : $dash }}</td></tr>
</table>

<h2>Legal &amp; Compliance</h2>
<table class="kv">
    <tr><td class="k">Malpractice insurance policy</td><td>{{ $doctor->malpractice_insurance_policy_no ?? $dash }}</td></tr>
    <tr><td class="k">Background check</td><td><span class="badge {{ $bg }}">{{ ucfirst($doctor->background_check_status ?? 'pending') }}</span></td></tr>
</table>

@if($includeAppointments)
    <h2>Appointment Summary — {{ $from->format('M j, Y') }} to {{ $to->format('M j, Y') }}</h2>
    <table class="stats">
        <tr>
            <td><div class="num">{{ $stats['total'] }}</div><div class="lbl">Total</div></td>
            <td><div class="num">{{ $stats['booked'] }}</div><div class="lbl">Booked</div></td>
            <td><div class="num">{{ $stats['completed'] }}</div><div class="lbl">Completed</div></td>
            <td><div class="num">{{ $stats['cancelled'] }}</div><div class="lbl">Cancelled</div></td>
            <td><div class="num">{{ $stats['no_show'] }}</div><div class="lbl">No-show</div></td>
            <td><div class="num">{{ $stats['morning'] }}</div><div class="lbl">Morning</div></td>
            <td><div class="num">{{ $stats['afternoon'] }}</div><div class="lbl">Afternoon</div></td>
        </tr>
    </table>

    <h2>Appointment List</h2>
    @if($appointments->isEmpty())
        <p style="color:#888;">No appointments in this period.</p>
    @else
        <table class="list">
            <thead><tr><th>#</th><th>Date</th><th>Time</th><th>Patient</th><th>Status</th></tr></thead>
            <tbody>
            @foreach($appointments as $i => $a)
                @php $t = \Carbon\Carbon::parse($a->scheduled_time); @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $t->format('D, M j, Y') }}</td>
                    <td>{{ $t->format('h:i A') }}</td>
                    <td>{{ $a->patient->name ?? 'N/A' }}</td>
                    <td><span class="badge {{ $statusClass($a->status) }}">{{ ucfirst($a->status) }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
@endif

<div class="footer">
    Generated on {{ now()->format('F j, Y \a\t h:i A') }} — Hospital Queue Optimization System — Confidential
</div>
</body>
</html>