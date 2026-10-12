@extends('layouts.main')
@section('content')
<div class="page-card p-4">
    <h3 class="mb-4"><i class="ti ti-calendar"></i> My appointments</h3>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    @foreach(['Today' => $today, 'Upcoming' => $upcoming, 'History' => $history] as $title => $list)
        <h5 class="mt-4 mb-3">{{ $title }} <span class="badge bg-secondary">{{ $list->count() }}</span></h5>

        @if($list->isEmpty())
            <p class="text-muted small">{{ $title === 'Today' ? 'No appointments today.' : 'Nothing here.' }}</p>
        @else
            <div class="row g-3">
            @foreach($list as $r)
                @php
                    $a = $r->appointment;
                    $live = $r->live;
                    [$cls, $label] = match ($r->state) {
                        'called'            => ['bg-danger', 'With doctor now'],
                        'waiting'           => ['bg-info text-dark', 'Checked in · waiting'],
                        'done'              => ['bg-success', 'Done'],
                        'cancelled'         => ['bg-secondary', 'Cancelled'],
                        'missed'            => ['bg-warning text-dark', 'Missed'],
                        'checkin', 'blocked'=> ['bg-primary', 'Today'],
                        default             => ['bg-light text-dark border', 'Upcoming'],
                    };
                @endphp
                <div class="col-md-6">
                    <div class="card h-100 p-3 {{ $r->state === 'called' ? 'border-danger border-2' : '' }}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="mb-0">{{ $a->doctor->user->name ?? 'Doctor' }}</h5>
                                <div class="text-muted">{{ $a->department->name ?? '' }}</div>
                            </div>
                            <span class="badge {{ $cls }}">{{ $label }}</span>
                        </div>
                        <div class="mt-2"><i class="ti ti-clock"></i> {{ $r->time->format('D, d M Y · h:i A') }}</div>

                        @if($r->state === 'called')
                            <div class="alert alert-danger mt-3 mb-0">
                                <strong>Queue no. #{{ $live['token'] }}: it's your turn.</strong><br>
                                Please go to {{ $live['room'] ? 'Room '.$live['room'] : "the doctor's room" }} now.
                            </div>
                        @elseif($r->state === 'waiting')
                            <div class="alert alert-info mt-3 mb-0">
                                <strong>Queue no. #{{ $live['token'] }}</strong>
                                · {{ $live['position'] ? 'Position '.$live['position'].' of '.$live['total'] : 'Position updating' }}
                                @if($live['expected_at'])<br>Expected around <strong>{{ $live['expected_at'] }}</strong>@endif
                                <br><a href="{{ route('patient.queue-status') }}">Open live status →</a>
                            </div>
                        @elseif($r->state === 'done')
                            <div class="text-success mt-3">
                                <i class="ti ti-circle-check"></i> Visit completed
                                {{ $r->entry?->completed_at ? 'at '.\Carbon\Carbon::parse($r->entry->completed_at)->format('h:i A') : '' }}
                                {{ $r->entry?->token_no ? '· Queue no. #'.$r->entry->token_no : '' }}
                            </div>
                        @elseif($r->state === 'checkin')
                            <form method="POST" action="{{ route('patient.appointment.checkin', $a) }}" class="mt-3">
                                @csrf
                                <button class="btn btn-brand w-100"><i class="ti ti-login"></i> Check In Now</button>
                            </form>
                        @elseif($r->state === 'blocked')
                            <div class="text-muted small mt-3">You are already in a queue today. You can check in for this one after that visit is finished.</div>
                        @elseif($r->state === 'upcoming')
                            <div class="text-muted small mt-3">Check-in opens on {{ $r->time->format('d M') }}.</div>
                        @endif
                    </div>
                </div>
            @endforeach
            </div>
        @endif
    @endforeach
</div>
<script>setInterval(() => location.reload(), 30000);</script>
@endsection