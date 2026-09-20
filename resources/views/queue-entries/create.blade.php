@extends('layouts.app')
@section('content')
<div class="container">
    <h2>Check In Patient</h2>
    <form action="{{ route('queue-entries.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label>Patient</label>
            <select name="patient_id" class="form-control">
                @foreach($patients as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
            </select>
        </div>
        <div class="mb-3">
            <label>Department</label>
            <select name="department_id" class="form-control">
                @foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
            </select>
        </div>
        <div class="mb-3">
            <label>Doctor (optional)</label>
            <select name="doctor_id" class="form-control">
                <option value="">-- Not assigned yet --</option>
                @foreach($doctors as $d)<option value="{{ $d->id }}">{{ $d->user->name }}</option>@endforeach
            </select>
        </div>
        <div class="mb-3">
            <label>Urgency</label>
            <select name="urgency_level" class="form-control">
                <option value="emergency">Emergency</option>
                <option value="urgent">Urgent</option>
                <option value="routine">Routine</option>
            </select>
        </div>
        <button class="btn btn-primary">Check In</button>
    </form>
</div>
@endsection