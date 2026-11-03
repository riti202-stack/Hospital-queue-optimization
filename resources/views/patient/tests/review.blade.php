@extends('layouts.main')
@section('content')
@php
    $rows = $tests->map(fn ($t) => ['name' => $t->name, 'room' => $t->room_no, 'prep' => $t->preparation, 'price' => $t->price])->all();
@endphp
<div class="page-card p-4" style="max-width:980px;margin:auto;">
    <a href="{{ route('patient.tests', ['tests' => $tests->pluck('id')->all()]) }}" class="small text-decoration-none"><i class="ti ti-arrow-left"></i> Change tests</a>
    <h3 class="mt-2 mb-1">Your test report</h3>
    <p class="text-muted">Check the tests and total, choose when you would like to come, and send it to the administration.</p>

    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    @include('patient.tests._report', [
        'rows' => $rows, 'total' => $total, 'code' => null, 'date' => now()->format('d M Y'),
        'meta' => ['Patient' => auth()->user()->name, 'Email' => auth()->user()->email, 'Number of tests' => count($rows)],
    ])

    <form method="POST" action="{{ route('patient.tests.store') }}" class="mt-4">
        @csrf
        @foreach($tests as $t)<input type="hidden" name="tests[]" value="{{ $t->id }}">@endforeach

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Preferred date</label>
                <input type="date" name="preferred_date" class="form-control" required
                       min="{{ today()->format('Y-m-d') }}" value="{{ old('preferred_date', $defaultDate->format('Y-m-d')) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Preferred time</label>
                <select name="preferred_time" class="form-select" required>
                    @for($h = \App\Models\TestRequest::OPEN_HOUR; $h < \App\Models\TestRequest::CLOSE_HOUR; $h++)
                        @foreach(['00', '30'] as $m)
                            @php $v = sprintf('%02d:%s', $h, $m); @endphp
                            <option value="{{ $v }}" {{ old('preferred_time', '09:00') === $v ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::createFromFormat('H:i', $v)->format('h:i A') }}
                            </option>
                        @endforeach
                    @endfor
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Prescribed by</label>
                <select name="doctor_id" class="form-select">
                    <option value="">Not listed / other</option>
                    @foreach($myDoctors as $d)
                        <option value="{{ $d->id }}" {{ old('doctor_id') == $d->id ? 'selected' : '' }}>{{ $d->user->name ?? 'Doctor' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">Note for the lab (optional)</label>
                <textarea name="patient_note" rows="2" class="form-control" placeholder="e.g. I can only come in the morning">{{ old('patient_note') }}</textarea>
            </div>
        </div>
        <p class="small text-muted mt-2">Lab hours: Saturday–Thursday, 8:00 AM – 6:00 PM. If your time is fully booked, we will suggest the nearest free time.</p>
        <button class="btn btn-brand btn-lg"><i class="ti ti-send"></i> Send to administration</button>
    </form>
</div>
@endsection