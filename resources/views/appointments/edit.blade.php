@extends('layouts.app')
@section('content')
<div class="container">
    <h2>Edit Appointment</h2>
    <form action="{{ route('appointments.update', $appointment) }}" method="POST">
        @csrf @method('PUT')
        <div class="mb-3">
            <label>Patient</label>
            <select name="patient_id" class="form-control">
                @foreach($patients as $p)
                    <option value="{{ $p->id }}" {{ $appointment->patient_id == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label>Doctor</label>
            <select name="doctor_id" class="form-control">
                @foreach($doctors as $d)
                    <option value="{{ $d->id }}" {{ $appointment->doctor_id == $d->id ? 'selected' : '' }}>{{ $d->user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label>Department</label>
            <select name="department_id" class="form-control">
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ $appointment->department_id == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label>Date & Time</label>
            <input type="datetime-local" name="scheduled_time" class="form-control" value="{{ \Carbon\Carbon::parse($appointment->scheduled_time)->format('Y-m-d\TH:i') }}">
        </div>
        <div class="mb-3">
            <label>Status</label>
            <select name="status" class="form-control">
                @foreach(['booked','completed','cancelled','no-show'] as $s)
                    <option value="{{ $s }}" {{ $appointment->status == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-primary">Update</button>
    </form>
</div>
@endsection