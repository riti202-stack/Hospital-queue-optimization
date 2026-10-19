@extends('layouts.main')
@section('content')
@php $fmt = fn($t) => \Carbon\Carbon::parse($t); @endphp

<div class="page-card p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <h3 class="mb-0"><i class="ti ti-calendar"></i> My Appointments</h3>
        <div class="d-flex gap-2">
            <span class="badge bg-light text-dark border p-2">Today: <strong>{{ $stats['total'] }}</strong></span>
            <span class="badge bg-info text-dark p-2">Checked in: <strong>{{ $stats['checkedIn'] }}</strong></span>
            <span class="badge bg-success p-2">Done: <strong>{{ $stats['done'] }}</strong></span>
        </div>
    </div>

    {{-- ===== TODAY ===== --}}
    <h5 class="mb-3">Today · {{ today()->format('l, d M Y') }}</h5>
    @if($today->isEmpty())
        <div class="alert alert-light border">No bookings today.</div>
    @else
        <div class="table-responsive mb-5">
            <table class="table align-middle bg-white">
                <thead class="table-light">
                    <tr><th>Time</th><th>Queue no.</th><th>Patient</th><th>Status</th></tr>
                </thead>
                <tbody>
                @foreach($today as $a)
                    @php [$label, $cls] = $statusOf($a); $e = $entries->get($a->id); @endphp
                    <tr class="{{ $label === 'With you now' ? 'table-primary' : '' }}">
                        <td class="fw-semibold">{{ $fmt($a->scheduled_time)->format('h:i A') }}</td>
                        <td>
                            @if($e?->token_no)<span class="badge bg-dark">#{{ $e->token_no }}</span>@else<span class="text-muted">—</span>@endif
                        </td>
                        <td>{{ $a->patient->name ?? 'Patient' }}</td>
                        <td>
                            <span class="badge {{ $cls }}">{{ $label }}</span>
                            @if($label === 'Waiting' && $e)
                                <small class="text-muted ms-1">since {{ $fmt($e->checked_in_at)->format('h:i A') }}</small>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- ===== UPCOMING ===== --}}
    <h5 class="mb-3">Upcoming <span class="badge bg-secondary">{{ $upcoming->count() }}</span></h5>
    @if($upcoming->isEmpty())
        <div class="alert alert-light border mb-5">No upcoming bookings.</div>
    @else
        <div class="table-responsive mb-5">
            <table class="table align-middle bg-white">
                <thead class="table-light"><tr><th>Date</th><th>Time</th><th>Patient</th></tr></thead>
                <tbody>
                @foreach($upcoming as $a)
                    <tr>
                        <td>{{ $fmt($a->scheduled_time)->format('D, d M Y') }}</td>
                        <td class="fw-semibold">{{ $fmt($a->scheduled_time)->format('h:i A') }}</td>
                        <td>{{ $a->patient->name ?? 'Patient' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- ===== HISTORY (last 7 days) ===== --}}
    <h5 class="mb-1">History</h5>
    <p class="text-muted small mb-3">Last 7 days. Older visits are removed from this list automatically.</p>

    @forelse($history as $date => $list)
        <h6 class="mt-3 mb-2 text-muted">
            {{ \Carbon\Carbon::parse($date)->isYesterday() ? 'Yesterday' : \Carbon\Carbon::parse($date)->format('l, d M') }}
            <span class="badge bg-light text-dark border">{{ $list->count() }}</span>
        </h6>
        <div class="table-responsive">
            <table class="table table-sm align-middle bg-white">
                <thead class="table-light"><tr><th>Time</th><th>Queue no.</th><th>Patient</th><th>Outcome</th></tr></thead>
                <tbody>
                @foreach($list as $a)
                    @php [$label, $cls] = $statusOf($a); $e = $entries->get($a->id); @endphp
                    <tr>
                        <td>{{ $fmt($a->scheduled_time)->format('h:i A') }}</td>
                        <td>@if($e?->token_no)#{{ $e->token_no }}@else<span class="text-muted">—</span>@endif</td>
                        <td>{{ $a->patient->name ?? 'Patient' }}</td>
                        <td>
                            <span class="badge {{ $cls }}">{{ $label }}</span>
                            @if($label === 'Done' && $e?->completed_at)
                                <small class="text-muted ms-1">at {{ $fmt($e->completed_at)->format('h:i A') }}</small>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <div class="alert alert-light border">No visits in the last 7 days.</div>
    @endforelse
</div>
<script>setTimeout(() => location.reload(), 30000);</script>
@endsection