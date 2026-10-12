@extends('layouts.main')
@section('content')
@php
    $badge = fn($u) => match($u) { 'emergency' => 'bg-danger', 'urgent' => 'bg-warning text-dark', default => 'bg-success-subtle text-success' };
@endphp

<div class="page-card p-4">
    @if(auth()->user()->role === 'admin')
        <a href="{{ route('queue-entries.department', $department) }}" class="text-decoration-none small">
            <i class="ti ti-arrow-left"></i> {{ $department->name }} doctors
        </a>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-center mt-2 mb-4 gap-2">
        <div>
            <h3 class="mb-0">{{ $doctor->user->name ?? 'Doctor' }}</h3>
            <div class="text-muted small">
                {{ $department->name }} · Room {{ $doctor->room_no ?? '—' }}
                @unless($doctor->is_available)<span class="badge bg-secondary ms-1">Unavailable</span>@endunless
            </div>
        </div>
        <div class="text-muted small text-end">
            Seen today: <strong>{{ $completedToday }}</strong> · Waiting: <strong>{{ $queue->count() }}</strong>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif

    {{-- Now seeing --}}
    @if($current)
        <div class="card border-primary mb-4">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <div class="small text-muted text-uppercase fw-bold">Now seeing</div>
                    <h5 class="mb-0"><span class="badge bg-dark me-1">#{{ $current->token_no }}</span>{{ $current->patient->name ?? 'Patient' }}
                        <span class="badge {{ $badge($current->urgency_level) }} ms-1">{{ ucfirst($current->urgency_level) }}</span>
                    </h5>
                    <small class="text-muted">Started {{ \Carbon\Carbon::parse($current->started_at)->diffForHumans() }}</small>
                </div>
                <a href="{{ route('queue-entries.edit', $current) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
            </div>
        </div>
    @endif

    {{-- Waiting list in priority order --}}
    @if($queue->isEmpty())
        <div class="alert alert-light border text-center py-4">No patients waiting for this doctor.</div>
    @else
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr><th>Position</th><th>Queue no.</th><th>Patient</th>
                    <th>Urgency</th><th>Waiting</th><th>Type</th><th>Priority</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                @foreach($queue as $i => $e)
                    <tr class="{{ $i === 0 ? 'table-success' : '' }}">
                        <td><strong>{{ $i + 1 }}</strong>@if($i === 0)<br><small class="text-success fw-semibold">Next</small>@endif</td>
                        <td><span class="badge bg-dark">#{{ $e->token_no }}</span></td><td class="fw-semibold">{{ $e->patient->name ?? 'Patient' }}</td>
                        <td><span class="badge {{ $badge($e->urgency_level) }}">{{ ucfirst($e->urgency_level) }}</span></td>
                        <td>{{ \Carbon\Carbon::parse($e->checked_in_at)->diffForHumans(null, true) }}</td>
                        <td class="small text-muted">
                            {{ $e->appointment_id ? 'Appointment '.\Carbon\Carbon::parse($e->appointment?->scheduled_time)->format('h:i A') : 'Walk-in' }}
                        </td>
                        <td>{{ number_format($e->priority_score, 1) }}</td>
                        <td class="text-end">
                            <a href="{{ route('queue-entries.edit', $e) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <form action="{{ route('queue-entries.destroy', $e) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove from queue?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
<script>setTimeout(() => location.reload(), 30000);</script>
@endsection