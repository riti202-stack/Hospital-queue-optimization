@php
    $cantCancel     = \App\Support\AppointmentRules::cannotChange($appointment);
    $cantReschedule = \App\Support\AppointmentRules::cannotChange($appointment, true);
@endphp

<div class="d-flex flex-wrap gap-1 align-items-center">
    @unless($cantReschedule)
        <a href="{{ route('patient.appointments.reschedule', $appointment) }}" class="btn btn-sm btn-outline-primary">
            <i class="ti ti-calendar-time"></i> {{ __('Reschedule') }}
        </a>
    @endunless

    @unless($cantCancel)
        <form method="POST" action="{{ route('patient.appointments.cancel', $appointment) }}" class="d-inline"
              onsubmit="if(!confirm('{{ __('Cancel this appointment?') }}')) return false;
                        const r = prompt('{{ __('Reason for cancelling (optional)') }}', '');
                        if (r === null) return false;
                        this.cancel_reason.value = r; return true;">
            @csrf
            <input type="hidden" name="cancel_reason">
            <button class="btn btn-sm btn-outline-danger"><i class="ti ti-x"></i> {{ __('Cancel') }}</button>
        </form>
    @endunless

    @if($cantCancel && !in_array($appointment->status, \App\Support\AppointmentRules::CLOSED, true))
        <small class="text-muted">{{ $cantCancel }}</small>
    @endif
</div>