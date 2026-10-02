@extends('layouts.main')
@section('content')
<div class="page-card p-4" style="max-width:600px;">
    <h3 class="mb-4"><i class="ti ti-edit"></i> Edit Appointment</h3>
    <form action="{{ route('appointments.update', $appointment) }}" method="POST">
        @csrf @method('PUT')
        <div class="mb-3">
            <label class="form-label">Patient</label>
            <select name="patient_id" class="form-select">
                @foreach($patients as $p)<option value="{{ $p->id }}" {{ $appointment->patient_id == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>@endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Doctor</label>
            <select name="doctor_id" class="form-select">
                @foreach($doctors as $d)<option value="{{ $d->id }}" {{ $appointment->doctor_id == $d->id ? 'selected' : '' }}>{{ $d->user->name }}</option>@endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Department</label>
            <select name="department_id" class="form-select">
                @foreach($departments as $dept)<option value="{{ $dept->id }}" {{ $appointment->department_id == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>@endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Date & time</label>
            <input type="datetime-local" name="scheduled_time" class="form-control" value="{{ \Carbon\Carbon::parse($appointment->scheduled_time)->format('Y-m-d\TH:i') }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                @foreach(['booked','completed','cancelled','no-show'] as $s)
                    <option value="{{ $s }}" {{ $appointment->status == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-brand"><i class="ti ti-check"></i> Update</button>
    </form>
</div>
@endsection