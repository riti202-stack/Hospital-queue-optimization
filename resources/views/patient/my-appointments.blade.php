@extends('layouts.main')

@section('content')
@php
    $who = fn ($a) => $a->dependent
        ? $a->dependent->name . ' (' . __($a->dependent->relation) . ')'
        : __('Myself');
    $statusBadge = fn ($s) => match ($s) {
        'booked'    => ['bg-primary', __('Booked')],
        'completed' => ['bg-success', __('Completed')],
        'cancelled' => ['bg-secondary', __('Cancelled')],
        default     => ['bg-light text-dark', ucfirst(str_replace('_', ' ', $s))],
    };
@endphp

<div class="page-card">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h3 class="mb-0"><i class="ti ti-calendar-event"></i> {{ __('My Appointments') }}</h3>
        <a href="{{ route('patient.book') }}" class="btn btn-brand"><i class="ti ti-plus"></i> {{ __('Book Appointment') }}</a>
    </div>

    @include('partials.flash')

    {{-- ================= UPCOMING ================= --}}
    <h5 class="mt-2">{{ __('Upcoming') }} <span class="badge bg-secondary">{{ $upcoming->count() }}</span></h5>

    @forelse($upcoming as $appt)
        @php
            $at      = \Carbon\Carbon::parse($appt->scheduled_time);
            $isToday = $at->isToday();
        @endphp
        <div class="card entry-card mb-3">
            <div class="card-body">
                <div class="row g-3 align-items-center">
                    <div class="col-md-3">
                        <div class="fw-bold fs-5">{{ $at->format('D, d M Y') }}</div>
                        <div class="text-muted">{{ $at->format('h:i A') }}</div>
                        @if($isToday)<span class="badge bg-warning text-dark mt-1">{{ __('Today') }}</span>@endif
                    </div>

                    <div class="col-md-4">
                        <div class="fw-semibold">{{ $appt->doctor?->displayName() }}</div>
                        <small class="text-muted">
                            {{ $appt->doctor?->department?->name }}
                            @if($appt->doctor?->room_no) · Room {{ $appt->doctor->room_no }} @endif
                        </small>
                    </div>

                    <div class="col-md-2">
                        <small class="text-muted d-block">{{ __('Patient') }}</small>
                        <span class="fw-semibold">{{ $who($appt) }}</span>
                    </div>

                    <div class="col-md-3 text-md-end">
                        @if(in_array($appt->id, $checkedIn))
                            <a href="{{ route('patient.queue-status') }}" class="btn btn-success btn-sm mb-1">
                                <i class="ti ti-list-numbers"></i> {{ __('My Queue') }}
                            </a>
                        @elseif($isToday)
                            <form method="POST" action="{{ route('patient.appointment.checkin', $appt) }}" class="d-inline">
                                @csrf
                                <button class="btn btn-brand btn-sm mb-1"><i class="ti ti-user-check"></i> {{ __('Check In') }}</button>
                            </form>
                        @endif

                        {{-- Reschedule / Cancel (shown only when allowed) --}}
                        <div class="d-flex justify-content-md-end">
                            @include('patient.appointments._actions', ['appointment' => $appt])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="alert alert-light border">{{ __('No upcoming appointments.') }}</div>
    @endforelse

    {{-- ================= HISTORY ================= --}}
    <h5 class="mt-4">{{ __('History') }}</h5>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="table-light">
                <tr>
                    <th>{{ __('Date') }}</th>
                    <th>{{ __('Doctor') }}</th>
                    <th>{{ __('Patient') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($past as $appt)
                    @php [$cls, $label] = $statusBadge($appt->status); @endphp
                    <tr>
                        <td class="small">{{ \Carbon\Carbon::parse($appt->scheduled_time)->format('d M Y, h:i A') }}</td>
                        <td>
                            {{ $appt->doctor?->displayName() }}
                            <small class="d-block text-muted">{{ $appt->doctor?->department?->name }}</small>
                        </td>
                        <td>{{ $who($appt) }}</td>
                        <td>
                            <span class="badge {{ $cls }}">{{ $label }}</span>
                            @if($appt->status === 'cancelled' && $appt->cancel_reason)
                                <small class="d-block text-muted">{{ $appt->cancel_reason }}</small>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($appt->status === 'completed')
                                <a href="{{ route('patient.records') }}" class="btn btn-sm btn-outline-primary">
                                    <i class="ti ti-notes-medical"></i> {{ __('Prescription') }} / {{ __('Rate visit') }}
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">{{ __('No visits yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection