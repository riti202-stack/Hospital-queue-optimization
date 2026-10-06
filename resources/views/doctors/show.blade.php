@extends('layouts.main')
@section('content')
<div class="page-card p-4">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div class="d-flex gap-3 align-items-center">
            @if($doctor->photo)
                <img src="{{ asset('storage/'.$doctor->photo) }}" class="rounded-circle" style="width:72px;height:72px;object-fit:cover;">
            @else
                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white" style="width:72px;height:72px;font-size:24px;">
                    {{ substr($doctor->user->name, 0, 1) }}
                </div>
            @endif
            <div>
                <h3 class="mb-0">{{ $doctor->user->name }}</h3>
                <p class="text-muted mb-0">{{ $doctor->specialization ?? 'Specialization not set' }} — {{ $doctor->department->name }}</p>
            </div>
        </div>
        <a href="{{ route('doctors.edit', $doctor) }}" class="btn btn-brand"><i class="ti ti-edit"></i> Edit</a>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <h5 class="mb-3">Personal & Contact</h5>
            <table class="table table-sm">
                <tr><td class="text-muted">Date of birth</td><td>{{ $doctor->date_of_birth ?? '—' }}</td></tr>
                <tr><td class="text-muted">Gender</td><td>{{ ucfirst($doctor->gender ?? '—') }}</td></tr>
                <tr><td class="text-muted">Personal phone</td><td>{{ $doctor->personal_phone ?? '—' }}</td></tr>
                <tr><td class="text-muted">Hospital email</td><td>{{ $doctor->user->email }}</td></tr>
                <tr><td class="text-muted">Address</td><td>{{ $doctor->residential_address ?? '—' }}</td></tr>
                <tr><td class="text-muted">Emergency contact</td><td>{{ $doctor->emergency_contact_name ?? '—' }} {{ $doctor->emergency_contact_phone ? '('.$doctor->emergency_contact_phone.')' : '' }}</td></tr>
            </table>
        </div>

        <div class="col-md-6">
            <h5 class="mb-3">Licensure & Specialization</h5>
            <table class="table table-sm">
                <tr><td class="text-muted">License number</td><td>{{ $doctor->license_number ?? '—' }}</td></tr>
                <tr><td class="text-muted">Issuing body</td><td>{{ $doctor->license_issuing_body ?? '—' }}</td></tr>
                <tr><td class="text-muted">License expiry</td><td>{{ $doctor->license_expiry ?? '—' }}</td></tr>
                <tr><td class="text-muted">Specialization</td><td>{{ $doctor->specialization ?? '—' }}</td></tr>
                <tr><td class="text-muted">Sub-specialty</td><td>{{ $doctor->sub_specialty ?? '—' }}</td></tr>
            </table>

            <h6 class="mt-3">Qualifications</h6>
            @forelse($doctor->qualifications as $q)
                <div class="small mb-1">{{ $q->degree }} — {{ $q->institution }} ({{ $q->passing_year }})</div>
            @empty
                <p class="text-muted small">No qualifications recorded.</p>
            @endforelse
        </div>

        <div class="col-md-6">
            <h5 class="mb-3">Employment & Operational</h5>
            <table class="table table-sm">
                <tr><td class="text-muted">Department</td><td>{{ $doctor->department->name }}</td></tr>
                <tr><td class="text-muted">Room</td><td>{{ $doctor->room_no ?? '—' }}</td></tr>
                <tr><td class="text-muted">Employment type</td><td>{{ ucfirst(str_replace('_',' ', $doctor->employment_type)) }}</td></tr>
                <tr><td class="text-muted">Date of joining</td><td>{{ $doctor->date_of_joining ?? '—' }}</td></tr>
                <tr><td class="text-muted">Working hours</td><td>{{ $doctor->working_hours ?? '—' }}</td></tr>
                <tr><td class="text-muted">Fee share</td><td>{{ $doctor->fee_share_percent ? $doctor->fee_share_percent.'%' : '—' }}</td></tr>
                <tr><td class="text-muted">Status</td><td><span class="badge {{ $doctor->is_available ? 'bg-success' : 'bg-secondary' }}">{{ $doctor->is_available ? 'Available' : 'Unavailable' }}</span></td></tr>
            </table>
        </div>

        <div class="col-md-6">
            <h5 class="mb-3">Legal & Compliance</h5>
            <table class="table table-sm">
                <tr><td class="text-muted">Malpractice insurance policy</td><td>{{ $doctor->malpractice_insurance_policy_no ?? '—' }}</td></tr>
                <tr>
                    <td class="text-muted">Background check</td>
                    <td>
                        @php $bc = match($doctor->background_check_status){'cleared'=>'bg-success','flagged'=>'bg-danger',default=>'bg-warning'}; @endphp
                        <span class="badge {{ $bc }}">{{ ucfirst($doctor->background_check_status) }}</span>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>
@endsection