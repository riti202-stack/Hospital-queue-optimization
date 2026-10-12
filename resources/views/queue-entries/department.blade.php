@extends('layouts.main')
@section('content')
@php
    $badge = fn($u) => match($u) { 'emergency' => 'bg-danger', 'urgent' => 'bg-warning text-dark', default => 'bg-success-subtle text-success' };
@endphp
<style>
    .q-card { display:block; background:#fff; border:1px solid #dfe8ee; border-radius:16px; padding:22px;
              text-decoration:none; color:inherit; height:100%; transition:.15s; }
    .q-card:hover { border-color:#0f6e56; box-shadow:0 6px 18px rgba(0,0,0,.08); transform:translateY(-2px); }
    .q-card.off { opacity:.55; }
    .q-num { font-size:28px; font-weight:700; color:#0f6e56; line-height:1; }
    .q-lbl { font-size:12px; color:#6c757d; }
    .seeing-dot { display:inline-block; width:9px; height:9px; border-radius:50%; background:#20c997; margin-right:4px; }
</style>

<div class="page-card p-4">
    <a href="{{ route('queue-entries.index') }}" class="text-decoration-none small"><i class="ti ti-arrow-left"></i> All departments</a>
    <div class="d-flex justify-content-between align-items-center mt-2 mb-4">
        <h3 class="mb-0"><i class="ti ti-stethoscope"></i> {{ $department->name }} — Doctors</h3>
        <a href="{{ route('queue-entries.create') }}" class="btn btn-brand"><i class="ti ti-plus"></i> Check In Patient</a>
    </div>

    <div class="row g-3">
        @forelse($doctors as $doc)
            <div class="col-sm-6 col-lg-4">
                <a href="{{ route('queue-entries.doctor', [$department, $doc]) }}" class="q-card {{ $doc->is_available ? '' : 'off' }}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="mb-1">{{ $doc->user->name ?? 'Doctor #'.$doc->id }}</h5>
                            <div class="small text-muted">
                                Room {{ $doc->room_no ?? '—' }}
                                @if($doc->specialization) · {{ $doc->specialization }} @endif
                            </div>
                        </div>
                        @if(! $doc->is_available)
                            <span class="badge bg-secondary">Unavailable</span>
                        @endif
                    </div>
                    <div class="d-flex gap-4 mt-3">
                        <div><div class="q-num">{{ $doc->waiting_count }}</div><div class="q-lbl">Waiting</div></div>
                    </div>
                    <div class="small mt-3">
                        @if($doc->current)
                            <span class="seeing-dot"></span> Now seeing <strong>#{{ $doc->current->token_no }} · {{ $doc->current->patient->name ?? 'a patient' }}</strong>
                        @else
                            <span class="text-muted">Not seeing anyone</span>
                        @endif
                    </div>
                </a>
            </div>
        @empty
            <p class="text-muted">No doctors in this department.</p>
        @endforelse
    </div>

    @if($unassigned->isNotEmpty())
        <h5 class="mt-5 mb-2">Unassigned walk-ins <span class="badge bg-warning text-dark">{{ $unassigned->count() }}</span></h5>
        <p class="text-muted small">These go to the first doctor in {{ $department->name }} who presses “Call next”.</p>
        <table class="table align-middle">
            <thead class="table-light"<tr><th>Position</th><th>Queue no.</th><th>Patient</th><th>Urgency</th><th>Waiting</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @foreach($unassigned as $i => $e)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td><span class="badge bg-dark">#{{ $e->token_no }}</span></td><td class="fw-semibold">{{ $e->patient->name ?? 'Patient' }}</td>
                    <td><span class="badge {{ $badge($e->urgency_level) }}">{{ ucfirst($e->urgency_level) }}</span></td>
                    <td>{{ \Carbon\Carbon::parse($e->checked_in_at)->diffForHumans(null, true) }}</td>
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
    @endif
</div>
<script>setTimeout(() => location.reload(), 30000);</script>
@endsection