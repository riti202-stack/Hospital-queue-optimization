@extends('layouts.main')
@section('content')
@php $name = $doctor->user->name ?? 'Doctor'; @endphp
<style>
    .slot input { display:none; }
    .slot span { display:inline-block; min-width:100px; text-align:center; padding:8px 10px; border:1px solid #cfdde6;
                 border-radius:10px; background:#fff; cursor:pointer; font-weight:600; font-size:14px; }
    .slot input:checked + span { background:#0f6e56; color:#fff; border-color:#0f6e56; }
    .slot input:disabled + span { background:#f1f3f5; color:#adb5bd; cursor:not-allowed; text-decoration:line-through; }
</style>

<div class="page-card p-4" style="max-width:960px;margin:auto;">
    <a href="{{ route('patient.department.doctors', $doctor->department_id) }}" class="small text-decoration-none">
        <i class="ti ti-arrow-left"></i> Back to {{ ucfirst($doctor->department->name ?? '') }} doctors
    </a>
    <h3 class="mt-2 mb-1">Book with {{ $name }}</h3>
    <p class="text-muted mb-4">Step 3 of 3 · Pick a date and time</p>

    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="d-flex gap-3 align-items-center p-3 mb-4" style="background:#f1faf7;border-radius:14px;">
        @if($doctor->photo)
            <img src="{{ asset('storage/'.$doctor->photo) }}" alt="{{ $name }}" style="width:64px;height:64px;border-radius:50%;object-fit:cover;">
        @endif
        <div>
            <div class="fw-bold">{{ $name }}</div>
            <div class="small text-muted">
                {{ $doctor->specialization ?? '' }} · Room {{ $doctor->room_no ?? '—' }}
                @if($doctor->working_hours) · {{ $doctor->working_hours }} @endif
            </div>
        </div>
    </div>

    <form method="GET" class="mb-3">
        <label class="form-label fw-semibold">Date</label>
        <input type="date" name="date" class="form-control" style="max-width:240px;"
               value="{{ $date->format('Y-m-d') }}"
               min="{{ today()->format('Y-m-d') }}" max="{{ today()->addDays(30)->format('Y-m-d') }}"
               onchange="this.form.submit()">
        <small class="text-muted">{{ $date->format('l, d F Y') }}</small>
    </form>

    <form method="POST" action="{{ route('patient.doctor.book.store', $doctor) }}">
        @csrf
        <input type="hidden" name="date" value="{{ $date->format('Y-m-d') }}">

        @foreach($slots as $period => $list)
            <h6 class="mt-3">{{ $period }}</h6>
            <div class="d-flex flex-wrap gap-2">
                @foreach($list as $s)
                    <label class="slot">
                        <input type="radio" name="time" value="{{ $s['time'] }}"
                               {{ $s['free'] ? '' : 'disabled' }} {{ old('time') === $s['time'] ? 'checked' : '' }}>
                        <span>{{ $s['label'] }}</span>
                    </label>
                @endforeach
            </div>
        @endforeach

        @if($freeCount === 0)
            <div class="alert alert-warning mt-3">No free slots on this date. Please choose another day.</div>
        @endif

        <button class="btn btn-brand btn-lg mt-4" {{ $freeCount ? '' : 'disabled' }}>
            <i class="ti ti-check"></i> Confirm booking
        </button>
    </form>
</div>
@endsection