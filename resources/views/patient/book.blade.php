@extends('layouts.main')
@section('content')
<div class="page-card">
    <h3 class="mb-4"><i class="ti ti-calendar-plus"></i> Book an appointment</h3>
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    <form action="{{ route('patient.book.store') }}" method="POST" class="row g-3">
        @csrf
        <div class="col-md-6">
            <label class="form-label">Department</label>
            <select name="department_id" id="department_id" class="form-select" required>
                <option value="">Select department</option>
                @foreach($departments as $d)
                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Doctor</label>
            <select name="doctor_id" class="form-select" required>
                <option value="">Select doctor</option>
                @foreach($doctors as $doc)
                    <option value="{{ $doc->id }}" data-dept="{{ $doc->department_id }}">
                        {{ $doc->user->name }} — {{ $doc->department->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Date & time</label>
            <input type="datetime-local" name="scheduled_time" class="form-control" required>
        </div>
        <div class="col-12">
            <button class="btn btn-brand"><i class="ti ti-check"></i> Book appointment</button>
        </div>
    </form>
</div>
@endsection