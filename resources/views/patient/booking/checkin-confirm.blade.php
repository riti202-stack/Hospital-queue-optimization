@extends('layouts.main')

@section('content')
@php
    $urgencies = [
        'routine'   => ['Routine',   'Regular check-up, follow-up or mild symptoms',            'ti-clipboard-heart', 'success'],
        'urgent'    => ['Urgent',    'High fever, strong pain or symptoms getting worse',       'ti-alert-triangle',  'warning'],
        'emergency' => ['Emergency', 'Chest pain, breathing trouble, heavy bleeding, fainting', 'ti-urgent',          'danger'],
    ];
    $picked = old('urgency_level', 'routine');
@endphp

<div class="page-card" style="max-width:880px;margin:auto">
    <a href="{{ route('patient.checkin.doctors', $department) }}" class="btn btn-link px-0 mb-2">
        <i class="ti ti-arrow-left"></i> {{ __('Back') }}
    </a>

    <h3 class="mb-1"><i class="ti ti-user-check"></i> {{ __('Confirm check-in') }}</h3>
    <p class="text-muted mb-4">{{ __('Check the details below, choose who is checking in and how urgent it is.') }}</p>

    @include('partials.flash')

    {{-- Summary --}}
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card entry-card h-100">
                <div class="card-body">
                    <small class="text-muted d-block">{{ __('Department') }}</small>
                    <h5 class="mb-2">{{ $department->name }}</h5>

                    <small class="text-muted d-block">{{ __('Doctor') }}</small>
                    @if($doctor)
                        <h5 class="mb-1">{{ $doctor->user->name ?? $doctor->name ?? 'Doctor' }}</h5>
                        @if($doctor->sub_specialty ?? $doctor->specialization ?? null)
                            <small class="text-muted d-block">{{ $doctor->sub_specialty ?? $doctor->specialization }}</small>
                        @endif
                        @if($doctor->room_no ?? null)
                            <span class="badge bg-success-subtle text-success mt-2"><i class="ti ti-door"></i> Room {{ $doctor->room_no }}</span>
                        @endif
                    @else
                        <h5 class="mb-1">{{ __('First available doctor') }}</h5>
                        <small class="text-muted">{{ __('You will be seen by the next free doctor in this department.') }}</small>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card entry-card h-100">
                <div class="card-body d-flex flex-column justify-content-center">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="live-dot"></span>
                        <div>
                            <div class="fs-2 fw-bold lh-1">{{ $waitingNow }}</div>
                            <small class="text-muted">{{ __('patients waiting now') }}</small>
                        </div>
                    </div>
                    @if(!is_null($waitMinutes))
                        <div>
                            <div class="fs-5 fw-semibold">
                                @if($waitMinutes < 1)
                                    {{ __('No wait — you could be called next') }}
                                @else
                                    ≈ {{ (int) round($waitMinutes) }} {{ __('minutes') }}
                                @endif
                            </div>
                            <small class="text-muted">{{ __('Estimated wait. Emergencies are always seen first, so this can change.') }}</small>
                        </div>
                    @else
                        <small class="text-muted">{{ __('Waiting time depends on which doctor becomes free first.') }}</small>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('patient.checkin.store') }}">
        @csrf
        <input type="hidden" name="department_id" value="{{ $department->id }}">
        <input type="hidden" name="doctor_id" value="{{ $doctor?->id }}">

        {{-- Who is checking in (family members + "Add family member" pop-up) --}}
        @include('partials.booking-for')

        {{-- Urgency --}}
        <label class="form-label fw-semibold mt-2">{{ __('How urgent is it?') }}</label>
        <div class="row g-2 mb-2">
            @foreach($urgencies as $value => [$label, $hint, $icon, $color])
                <div class="col-md-4">
                    <input type="radio" class="btn-check" name="urgency_level" id="urg-{{ $value }}"
                           value="{{ $value }}" @checked($picked === $value) required>
                    <label class="btn btn-outline-{{ $color }} w-100 h-100 text-start p-3" for="urg-{{ $value }}">
                        <i class="ti {{ $icon }} fs-4"></i>
                        <strong class="d-block mt-1">{{ __($label) }}</strong>
                        <small class="d-block">{{ __($hint) }}</small>
                    </label>
                </div>
            @endforeach
        </div>
        @error('urgency_level')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
        @error('doctor_id')<div class="text-danger small mb-2">{{ $message }}</div>@enderror

        <div class="alert alert-light border small mt-3 mb-3">
            <i class="ti ti-info-circle"></i>
            {{ __('Your queue number appears on the next page. Keep the page open — it updates by itself and alerts you with a sound when it is your turn.') }}
        </div>

        <button class="btn btn-brand btn-lg w-100">
            <i class="ti ti-check"></i> {{ __('Check in now') }}
        </button>
    </form>
</div>
@endsection