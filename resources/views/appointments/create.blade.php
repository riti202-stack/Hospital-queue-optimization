@extends('layouts.app')
@section('content')
<div class="container">
    <h2>Book Appointment</h2>
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    <form action="{{ route('appointments.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label>Patient</label>
            <select name="patient_id" class="form-control">
                @foreach($patients as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label>Doctor</label>
            <select name="doctor_id" class="form-control">
                @foreach($doctors as $d)
                    <option value="{{ $d->id }}">{{ $d->user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label>Department</label>
            <select name="department_id" class="form-control">
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label>Date & Time</label>
            <input type="datetime-local" name="scheduled_time" class="form-control">
        </div>
        <button class="btn btn-primary">Book</button>
    </form>
</div>
@endsection