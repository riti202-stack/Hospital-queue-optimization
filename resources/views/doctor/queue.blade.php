@extends('layouts.main')
@section('content')
@php
    $badge = fn($u) => match($u) {
        'emergency' => 'bg-danger',
        'urgent'    => 'bg-warning text-dark',
        default     => 'bg-success-subtle text-success',
    };
@endphp

<style>
    .next-card { border: 2px solid #0f6e56; border-radius: 16px; background: #f1faf7; }
    .next-card.emergency { border-color: #dc3545; background: #fff5f5; }
    .pos-circle { width: 34px; height: 34px; border-radius: 50%; background: #e8f5f1; color: #0f6e56;
                  display: inline-flex; align-items: center; justify-content: center; font-weight: 700; }
    .big-pos { width: 64px; height: 64px; font-size: 28px; background: #0f6e56; color: #fff; }
    .queue-row td { vertical-align: middle; }
    .live-dot { width: 9px; height: 9px; border-radius: 50%; background: #20c997; display: inline-block; }
</style>

<div class="page-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"><i class="ti ti-stethoscope"></i> My Queue</h3>
        <div class="text-muted small text-end">
            <span class="live-dot"></span> Live · refreshes every 30 s<br>
            Seen today: <strong>{{ $seenToday }}</strong> · Avg consultation: <strong>{{ $avgMinutes }} min</strong>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif

    {{-- ===== NOW SEEING ===== --}}
    @if($current)
        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <div class="text-muted small text-uppercase">Now seeing</div>
                    <h5 class="mb-0">{{ $current->patient->name ?? 'Patient' }}
                        <span class="badge {{ $badge($current->urgency_level) }} ms-1">{{ $current->urgency_level }}</span>
                    </h5>
                    <small class="text-muted">Started {{ \Carbon\Carbon::parse($current->started_at)->diffForHumans() }}</small>
                </div>
                <div class="d-flex gap-2">
                    {{-- Paste your existing "Send for tests" button/form here, using $current --}}
                    <form method="POST" action="{{ route('doctor.queue.complete') }}">
                        @csrf
                        <button class="btn btn-outline-secondary"><i class="ti ti-check"></i> Complete only</button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- ===== NEXT PATIENT ===== --}}
    @if($next)
        @php $n = $next['entry']; @endphp
        <div class="next-card {{ $n->urgency_level === 'emergency' ? 'emergency' : '' }} p-4 mb-4">
            <div class="d-flex flex-wrap align-items-center gap-4">
                <span class="pos-circle big-pos">1</span>
                <div class="flex-grow-1">
                    <div class="text-muted small text-uppercase fw-semibold">Call next</div>
                    <h2 class="mb-1">{{ $n->patient->name ?? 'Patient' }}
                        <span class="badge {{ $badge($n->urgency_level) }} fs-6 align-middle">{{ $n->urgency_level }}</span>
                    </h2>
                    <div class="mb-1"><i class="ti ti-info-circle"></i> {{ $next['reason'] }}</div>
                    <small class="text-muted">
                        Checked in {{ \Carbon\Carbon::parse($n->checked_in_at)->format('h:i A') }}
                        @if($n->appointment?->scheduled_time)
                            · Appointment {{ \Carbon\Carbon::parse($n->appointment->scheduled_time)->format('h:i A') }}
                        @else
                            · Walk-in
                        @endif
                        · Score {{ number_format($n->priority_score, 1) }}
                    </small>
                </div>
                <form method="POST" action="{{ route('doctor.queue.next') }}">
                    @csrf
                    <button class="btn btn-brand btn-lg px-4">
                        <i class="ti ti-phone-call"></i> {{ $current ? 'Complete & call next' : 'Call in' }}
                    </button>
                </form>
            </div>
        </div>
    @elseif(! $current)
        <div class="alert alert-light border text-center py-4">
            <i class="ti ti-mood-smile fs-3"></i><br>No patients waiting.
        </div>
    @else
        <div class="alert alert-light border">No one else is waiting. Complete the current patient when done.</div>
    @endif

    {{-- ===== UP NEXT ===== --}}
    @if($upcoming->isNotEmpty())
        <h5 class="mb-2">Up next <span class="badge bg-secondary">{{ $upcoming->count() }}</span></h5>
        <p class="text-muted small mb-2">
            Order is set automatically: emergencies first, then everyone else by urgency plus time waited,
            so nobody waits forever. Expected times are based on your average consultation time.
        </p>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead class="table-light">
                    <tr><th>#</th><th>Patient</th><th>Why this position</th><th>Expected</th><th class="text-end"></th></tr>
                </thead>
                <tbody>
                @foreach($upcoming as $row)
                    @php $e = $row['entry']; @endphp
                    <tr class="queue-row">
                        <td><span class="pos-circle">{{ $row['position'] }}</span></td>
                        <td>
                            <div class="fw-semibold">{{ $e->patient->name ?? 'Patient' }}</div>
                            <span class="badge {{ $badge($e->urgency_level) }}">{{ $e->urgency_level }}</span>
                        </td>
                        <td class="small text-muted">{{ $row['reason'] }}</td>
                        <td class="small">~{{ $row['expectedAt']->format('h:i A') }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('doctor.queue.call', $e) }}"
                                  onsubmit="return confirm('Call {{ addslashes($e->patient->name ?? 'this patient') }} before patient #1?')">
                                @csrf
                                <button class="btn btn-sm btn-outline-secondary" title="Call out of turn">
                                    <i class="ti ti-arrow-bar-to-up"></i> Call now
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<script>
    // Auto-refresh so new arrivals and re-ranking appear without reloading
    setTimeout(() => window.location.reload(), 30000);
</script>
@endsection