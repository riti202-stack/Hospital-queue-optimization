@extends('layouts.main')
@section('content')
<div class="page-card p-4" style="max-width:600px;">
    <h3 class="mb-4"><i class="ti ti-plus"></i> Book Appointment</h3>
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <form action="{{ route('appointments.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label">Patient</label>
            <select name="patient_id" class="form-select">
                @foreach($patients as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Doctor</label>
            <select name="doctor_id" class="form-select">
                @foreach($doctors as $d)<option value="{{ $d->id }}">{{ $d->user->name }}</option>@endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Department</label>
            <select name="department_id" class="form-select">
                @foreach($departments as $dept)<option value="{{ $dept->id }}">{{ $dept->name }}</option>@endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Date & time</label>
            <input type="datetime-local" name="scheduled_time" class="form-control">
        </div>
        <button class="btn btn-brand"><i class="ti ti-check"></i> Book</button>
    </form>
</div>
@endsection