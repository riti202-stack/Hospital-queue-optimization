@extends('layouts.main')

@section('content')
<div class="page-card" style="max-width:900px;margin:auto">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="{{ route('patient.records') }}" class="btn btn-link px-0"><i class="ti ti-arrow-left"></i> {{ __('Back') }}</a>
        <a href="{{ route('patient.records.prescription.pdf', $rx) }}" class="btn btn-brand"><i class="ti ti-download"></i> {{ __('Download PDF') }}</a>
    </div>

    <div class="d-flex justify-content-between border-bottom pb-3 mb-3">
        <div>
            <h4 class="mb-0">{{ $rx->doctor?->displayName() }}</h4>
            <small class="text-muted">{{ $rx->doctor?->department?->name }}</small>
        </div>
        <div class="text-end">
            <strong>{{ __('Prescription') }} {{ $rx->code() }}</strong><br>
            <small class="text-muted">{{ $rx->created_at->format('d M Y, g:i A') }}</small>
        </div>
    </div>

    <p><span class="text-muted">{{ __('Patient') }}:</span> <strong>{{ $rx->dependent ? $rx->dependent->name.' ('.__($rx->dependent->relation).')' : auth()->user()->name }}</strong></p>

    @if($rx->symptoms)<p><span class="text-muted">{{ __('Symptoms') }}:</span> {{ $rx->symptoms }}</p>@endif
    <p><span class="text-muted">{{ __('Diagnosis') }}:</span> <strong>{{ $rx->diagnosis }}</strong></p>

    <h5 class="mt-4"><span class="text-success fw-bold fs-3 me-1">℞</span> {{ __('Medicines') }}</h5>
    @if($rx->items->isNotEmpty())
        <table class="table">
            <thead class="table-light"><tr><th>#</th><th>{{ __('Medicine') }}</th><th>{{ __('Dosage') }}</th><th>{{ __('Duration') }}</th><th>{{ __('Instruction') }}</th></tr></thead>
            <tbody>
                @foreach($rx->items as $i => $it)
                    <tr><td>{{ $i + 1 }}</td><td class="fw-semibold">{{ $it->medicine }}</td><td>{{ $it->dosage }}</td><td>{{ $it->duration }}</td><td>{{ $it->instruction ?? '—' }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="text-muted">{{ __('No medicines prescribed.') }}</p>
    @endif

    @if($rx->tests_advised)<p><span class="text-muted">{{ __('Tests advised') }}:</span> {{ $rx->tests_advised }}</p>@endif
    @if($rx->advice)<p><span class="text-muted">{{ __('Advice') }}:</span> {{ $rx->advice }}</p>@endif
    @if($rx->follow_up_date)
        <div class="alert alert-info"><i class="ti ti-calendar-event"></i> {{ __('Follow-up date') }}: <strong>{{ $rx->follow_up_date->format('D, d M Y') }}</strong></div>
    @endif
</div>
@endsection