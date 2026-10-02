@extends('layouts.main')
@section('content')
<div class="page-card p-4" style="max-width:600px;">
    <h3 class="mb-4"><i class="ti ti-plus"></i> Check In Patient</h3>
    <form action="{{ route('queue-entries.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label">Patient</label>
            <select name="patient_id" class="form-select">
                @foreach($patients as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Department</label>
            <select name="department_id" class="form-select">
                @foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Doctor (optional)</label>
            <select name="doctor_id" class="form-select">
                <option value="">-- Not assigned yet --</option>
                @foreach($doctors as $d)<option value="{{ $d->id }}">{{ $d->user->name }}</option>@endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Urgency</label>
            <select name="urgency_level" class="form-select">
                <option value="emergency">Emergency</option>
                <option value="urgent">Urgent</option>
                <option value="routine">Routine</option>
            </select>
        </div>
        <button class="btn btn-brand"><i class="ti ti-check"></i> Check In</button>
    </form>
</div>
@endsection