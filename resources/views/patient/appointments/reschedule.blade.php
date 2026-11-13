@extends('layouts.main')

@section('content')
@php $current = \App\Support\AppointmentRules::slotAt($appointment); @endphp
<div class="page-card" style="max-width:860px;margin:auto">
    <a href="{{ route('patient.appointments') }}" class="btn btn-link px-0"><i class="ti ti-arrow-left"></i> {{ __('Back') }}</a>
    <h3><i class="ti ti-calendar-time"></i> {{ __('Reschedule appointment') }}</h3>
    <p class="mb-1">{{ __('Doctor') }}: <strong>{{ $appointment->doctor?->displayName() }}</strong></p>
    <p class="text-muted">{{ __('Current time') }}: <strong>{{ $current?->format('D, d M Y · g:i A') }}</strong></p>

    @include('partials.flash')

    <form method="GET" class="row g-2 align-items-end mb-4">
        <div class="col-sm-6">
            <label class="form-label">{{ __('Choose a date') }}</label>
            <input type="date" name="date" class="form-control" value="{{ $date->toDateString() }}"
                   min="{{ today()->toDateString() }}" max="{{ today()->addDays(30)->toDateString() }}">
        </div>
        <div class="col-sm-3">
            <button class="btn btn-outline-primary w-100">{{ __('Show slots') }}</button>
        </div>
    </form>

    <form method="POST" action="{{ route('patient.appointments.reschedule.update', $appointment) }}">
        @csrf
        @method('PUT')
        <input type="hidden" name="date" value="{{ $date->toDateString() }}">

        <h6>{{ __('Available times') }} — {{ $date->format('D, d M Y') }}</h6>

        @if(collect($slots)->filter()->isEmpty())
            <div class="alert alert-warning">{{ __('No free slots on this day. Please choose another date.') }}</div>
        @else
            <div class="d-flex flex-wrap gap-2 mb-3">
                @foreach($slots as $time => $free)
                    @php $id = 't'.str_replace(':', '', $time); @endphp
                    <input type="radio" class="btn-check" name="time" id="{{ $id }}" value="{{ $time }}" @disabled(!$free) @checked(old('time') === $time) required>
                    <label class="btn btn-sm {{ $free ? 'btn-outline-success' : 'btn-outline-secondary' }}" for="{{ $id }}">
                        {{ \Carbon\Carbon::parse($time)->format('g:i A') }}
                    </label>
                @endforeach
            </div>
            <button class="btn btn-brand"><i class="ti ti-check"></i> {{ __('Confirm new time') }}</button>
        @endif

        <small class="d-block text-muted mt-3">{{ __('You can reschedule :left more time(s).', ['left' => $left]) }}</small>
    </form>
</div>
@endsection