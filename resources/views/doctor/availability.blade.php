@extends('layouts.main')

@section('content')
@php
    $doc = $doctor ?? \App\Models\Doctor::with('department')->where('user_id', auth()->id())->first();
    $on  = (bool) ($doc->is_available ?? false);

    $waiting  = \App\Models\QueueEntry::where('doctor_id', $doc->id)->where('status', 'waiting')
                    ->where('checked_in_at', '>=', today())->count();
    $seen     = \App\Models\QueueEntry::where('doctor_id', $doc->id)->where('status', 'completed')
                    ->whereDate('completed_at', today())->count();
    $bookings = \App\Models\Appointment::where('doctor_id', $doc->id)->where('status', 'booked')
                    ->whereDate('scheduled_time', today())->count();
@endphp

<style>
    .avail-card { max-width: 640px; margin: 0 auto; }
    .status-dot { width: 14px; height: 14px; border-radius: 50%; display: inline-block; }
    .status-dot.on  { background: #20c997; box-shadow: 0 0 0 5px rgba(32,201,151,.18); }
    .status-dot.off { background: #adb5bd; box-shadow: 0 0 0 5px rgba(173,181,189,.2); }
    .mini-stat { background: #f4f8f7; border-radius: 12px; padding: .65rem .5rem; text-align: center; }
    .mini-stat .num { font-size: 1.35rem; font-weight: 700; color: #0f6e56; line-height: 1.1; }
    .mini-stat small { color: #6c757d; }
</style>

<div class="page-card avail-card p-4">
    @if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif

    {{-- Status + toggle --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <span class="status-dot {{ $on ? 'on' : 'off' }}"></span>
            <div>
                <div class="fw-bold fs-5 mb-0">{{ $on ? 'Available' : 'Not available' }}</div>
                <small class="text-muted">
                    {{ $on ? 'Patients can book and check in with you.' : 'New bookings and check-ins are paused.' }}
                </small>
            </div>
        </div>

        <form method="POST" action="{{ route('doctor.availability.update') }}">
            @csrf
            <input type="hidden" name="is_available" value="{{ $on ? 0 : 1 }}">
            <button class="btn {{ $on ? 'btn-outline-secondary' : 'btn-brand' }}">
                <i class="ti {{ $on ? 'ti-player-pause' : 'ti-player-play' }}"></i>
                {{ $on ? 'Pause' : 'Go available' }}
            </button>
        </form>
    </div>

    {{-- Today --}}
    <div class="row g-2 mt-3">
        <div class="col-4"><div class="mini-stat"><div class="num">{{ $waiting }}</div><small>waiting</small></div></div>
        <div class="col-4"><div class="mini-stat"><div class="num">{{ $seen }}</div><small>seen today</small></div></div>
        <div class="col-4"><div class="mini-stat"><div class="num">{{ $bookings }}</div><small>bookings today</small></div></div>
    </div>

    {{-- Details --}}
    <div class="d-flex flex-wrap gap-3 small text-muted mt-3">
        @if($doc->department?->name)<span><i class="ti ti-building-hospital"></i> {{ $doc->department->name }}</span>@endif
        @if($doc->room_no)<span><i class="ti ti-door"></i> Room {{ $doc->room_no }}</span>@endif
        @if($doc->visiting_hours)<span><i class="ti ti-clock"></i> {{ $doc->visiting_hours }}</span>@endif
    </div>

    @if(! $on && $waiting > 0)
        <div class="alert alert-warning small py-2 mt-3 mb-0">
            <i class="ti ti-alert-triangle"></i>
            {{ $waiting }} patient(s) are still waiting for you. Pausing does not remove them —
            <a href="{{ route('doctor.queue') }}">open My Queue</a> to see them.
        </div>
    @endif
</div>
@endsection