@extends('layouts.main')
@section('content')
@php
    $badge = fn($u) => match($u) {
        'emergency' => 'bg-danger',
        'urgent'    => 'bg-warning text-dark',
        default     => 'bg-success-subtle text-success',
    };
    $waited = fn($e) => \Carbon\Carbon::parse($e->checked_in_at)->diffForHumans(null, true);
@endphp

<style>
    .step-label { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #6c757d; }
    .pick-btn { border-radius: 12px; padding: 10px 16px; font-weight: 600; }
    .pick-btn .badge { font-size: 11px; }
    .seeing-dot { display: inline-block; width: 9px; height: 9px; border-radius: 50%; background: #20c997; margin-right: 4px; }
</style>

<div class="page-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="ti ti-list-details"></i> Live Queue</h3>
        <a href="{{ route('queue-entries.create') }}" class="btn btn-brand"><i class="ti ti-plus"></i> Check In Patient</a>
    </div>

    @if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif

    {{-- ===== STEP 1: Department (always visible) ===== --}}
    <div class="step-label mb-2">1 · Choose department</div>
    <div class="d-flex flex-wrap gap-2 mb-4">
        @foreach($departments as $d)
            <a href="{{ route('queue-entries.index', ['department' => $d->id]) }}"
               class="btn pick-btn {{ $department?->id === $d->id ? 'btn-brand' : 'btn-outline-secondary' }}">
                {{ $d->name }}
                <span class="badge {{ $department?->id === $d->id ? 'bg-light text-dark' : 'bg-secondary' }} ms-1">{{ $d->active_count }}</span>
            </a>
        @endforeach
    </div>

    @if(! $department)
        <div class="alert alert-light border text-center py-4 mb-0">
            <i class="ti ti-building-hospital fs-3"></i><br>
            Choose a department to see its doctors and queues.
        </div>
    @else
        {{-- ===== STEP 2: Doctor (always visible for this department) ===== --}}
        <div class="step-label mb-2">2 · Choose doctor in {{ $department->name }}</div>
        <div class="d-flex flex-wrap gap-2 mb-4">
            @forelse($doctors as $doc)
                @php $on = $selectedDoctor?->id === $doc->id; @endphp
                <a href="{{ route('queue-entries.index', ['department' => $department->id, 'doctor' => $doc->id]) }}"
                   class="btn pick-btn {{ $on ? 'btn-brand' : 'btn-outline-secondary' }} {{ $doc->is_available ? '' : 'opacity-50' }}"
                   title="{{ $doc->is_available ? 'Room '.($doc->room_no ?? '—') : 'Unavailable' }}">
                    @if($doc->is_seeing)<span class="seeing-dot" title="Seeing a patient now"></span>@endif
                    {{ $doc->user->name ?? 'Doctor #'.$doc->id }}
                    <span class="badge {{ $on ? 'bg-light text-dark' : 'bg-secondary' }} ms-1">{{ $doc->waiting_count }}</span>
                </a>
            @empty
                <span class="text-muted">No doctors in this department.</span>
            @endforelse

            @if($unassignedCount)
                <a href="{{ route('queue-entries.index', ['department' => $department->id, 'doctor' => 'unassigned']) }}"
                   class="btn pick-btn {{ $showUnassigned ? 'btn-warning' : 'btn-outline-warning' }}">
                    Unassigned walk-ins <span class="badge bg-dark ms-1">{{ $unassignedCount }}</span>
                </a>
            @endif
        </div>

        @if(! $selectedDoctor && ! $showUnassigned)
            <div class="alert alert-light border text-center py-4 mb-0">
                <i class="ti ti-stethoscope fs-3"></i><br>
                Choose a doctor to see their queue. The number on each button is how many patients are waiting;
                a green dot means the doctor is with a patient now.
            </div>
        @else
            {{-- ===== STEP 3: The selected queue ===== --}}
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <h5 class="mb-0">
                    @if($showUnassigned)
                        Unassigned walk-ins — {{ $department->name }}
                    @else
                        {{ $selectedDoctor->user->name ?? 'Doctor' }}
                        <small class="text-muted">· Room {{ $selectedDoctor->room_no ?? '—' }}</small>
                        @unless($selectedDoctor->is_available)<span class="badge bg-secondary ms-1">Unavailable</span>@endunless
                    @endif
                </h5>
                @unless($showUnassigned)
                    <small class="text-muted">Seen today: <strong>{{ $completedToday }}</strong> · Waiting: <strong>{{ $queue->count() }}</strong></small>
                @endunless
            </div>

            @if($showUnassigned)
                <p class="text-muted small">These patients go to the first doctor in {{ $department->name }} who presses “Call next”.</p>
            @endif

            {{-- Now seeing --}}
            @if($current)
                <div class="card border-primary mb-3">
                    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <div class="step-label">Now seeing</div>
                            <h5 class="mb-0">{{ $current->patient->name ?? 'Patient' }}
                                <span class="badge {{ $badge($current->urgency_level) }} ms-1">{{ ucfirst($current->urgency_level) }}</span>
                            </h5>
                            <small class="text-muted">Started {{ \Carbon\Carbon::parse($current->started_at)->diffForHumans() }}</small>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('queue-entries.edit', $current) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Ordered waiting list --}}
            @if($queue->isEmpty())
                <div class="alert alert-light border mb-0">No patients waiting.</div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr><th>#</th><th>Patient</th><th>Urgency</th><th>Waiting</th><th>Type</th><th>Priority</th><th class="text-end">Actions</th></tr>
                        </thead>
                        <tbody>
                        @foreach($queue as $i => $e)
                            <tr class="{{ $i === 0 ? 'table-success' : '' }}">
                                <td><strong>{{ $i + 1 }}</strong>@if($i === 0)<br><small class="text-success fw-semibold">Next</small>@endif</td>
                                <td class="fw-semibold">{{ $e->patient->name ?? 'Patient' }}</td>
                                <td><span class="badge {{ $badge($e->urgency_level) }}">{{ ucfirst($e->urgency_level) }}</span></td>
                                <td>{{ $waited($e) }}</td>
                                <td class="small text-muted">
                                    {{ $e->appointment_id ? 'Appointment '.\Carbon\Carbon::parse($e->appointment?->scheduled_time)->format('h:i A') : 'Walk-in' }}
                                </td>
                                <td>{{ number_format($e->priority_score, 1) }}</td>
                                <td class="text-end">
                                    <a href="{{ route('queue-entries.edit', $e) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <form action="{{ route('queue-entries.destroy', $e) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this patient from the queue?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
    @endif
</div>

<script>setTimeout(() => location.reload(), 30000);</script>
@endsection