@extends('layouts.main')
@section('content')
@php
    [$label, $cls] = $r->statusBadge();
    $rows = $r->items->map(fn ($i) => ['name' => $i->test_name, 'room' => $i->labTest?->room_no, 'prep' => $i->labTest?->preparation, 'price' => $i->price])->all();
    $meta = [
        'Patient'        => $r->patient->name ?? auth()->user()->name,
        'Prescribed by'  => $r->doctor?->user?->name ?? 'Not specified',
        'Preferred time' => $r->preferred_at->format('D, d M Y · h:i A'),
        'Status'         => $label,
    ];
    if (in_array($r->status, ['scheduled', 'completed'])) {
        $meta['Test date']    = $r->scheduled_at->format('D, d M Y');
        $meta['Test time']    = $r->scheduled_at->format('h:i A');
        $meta['Room']         = $r->room_no;
        $meta['Conducted by'] = $r->conducted_by;
    }
@endphp
<style>@media print { nav, .site-footer, .no-print { display:none !important; } .page-card { box-shadow:none !important; } }</style>

<div class="page-card p-4" style="max-width:980px;margin:auto;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
        <a href="{{ route('patient.tests.requests') }}" class="small text-decoration-none"><i class="ti ti-arrow-left"></i> My test reports</a>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="ti ti-printer"></i> Print / save as PDF</button>
    </div>

    @if(session('success'))<div class="alert alert-success no-print">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger no-print">{{ $errors->first() }}</div>@endif

    {{-- ===== Status panel ===== --}}
    <div class="no-print mb-4">
        @switch($r->status)
            @case('pending')
                <div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div><strong>Sent to the administration.</strong> We will confirm your time soon
                        (you asked for {{ $r->preferred_at->format('D, d M · h:i A') }}).</div>
                    <form method="POST" action="{{ route('patient.tests.requests.cancel', $r) }}" onsubmit="return confirm('Cancel this test request?')">
                        @csrf <button class="btn btn-sm btn-outline-danger">Cancel request</button>
                    </form>
                </div>
                @break

            @case('suggested')
                <div class="card border-info border-2">
                    <div class="card-body">
                        <h5 class="text-info"><i class="ti ti-message-circle"></i> Message from the administration</h5>
                        <p class="mb-3">{{ $r->admin_message }}</p>
                        <div class="p-3 mb-3 rounded-3" style="background:#eef8fc;">
                            <small class="text-muted d-block">Suggested time</small>
                            <span class="fs-4 fw-bold">{{ $r->suggested_at->format('l, d M Y · h:i A') }}</span>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('patient.tests.requests.accept', $r) }}">
                                @csrf <button class="btn btn-brand"><i class="ti ti-check"></i> Accept this time</button>
                            </form>
                            <button class="btn btn-outline-secondary" type="button"
                                    onclick="document.getElementById('declineBox').classList.toggle('d-none')">Decline</button>
                        </div>
                        <form method="POST" action="{{ route('patient.tests.requests.decline', $r) }}" id="declineBox" class="d-none mt-3 border-top pt-3">
                            @csrf
                            <p class="small mb-2">Suggest another time, or leave it empty to cancel the request.</p>
                            <div class="row g-2">
                                <div class="col-md-5"><input type="date" name="preferred_date" class="form-control" min="{{ today()->format('Y-m-d') }}"></div>
                                <div class="col-md-4">
                                    <select name="preferred_time" class="form-select">
                                        <option value="">Time</option>
                                        @for($h = \App\Models\TestRequest::OPEN_HOUR; $h < \App\Models\TestRequest::CLOSE_HOUR; $h++)
                                            @foreach(['00', '30'] as $m)
                                                @php $v = sprintf('%02d:%s', $h, $m); @endphp
                                                <option value="{{ $v }}">{{ \Carbon\Carbon::createFromFormat('H:i', $v)->format('h:i A') }}</option>
                                            @endforeach
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-md-3"><button class="btn btn-outline-danger w-100">Send</button></div>
                            </div>
                        </form>
                    </div>
                </div>
                @break

            @case('accepted')
                <div class="alert alert-primary">You accepted <strong>{{ $r->suggested_at->format('D, d M · h:i A') }}</strong>. The administration is confirming your booking.</div>
                @break

            @case('scheduled')
                <div class="card border-success border-2">
                    <div class="card-body">
                        <h5 class="text-success"><i class="ti ti-calendar-check"></i> Your tests are booked</h5>
                        <div class="row g-3 mt-1">
                            <div class="col-6 col-md-3"><small class="text-muted d-block">Date</small><strong>{{ $r->scheduled_at->format('D, d M Y') }}</strong></div>
                            <div class="col-6 col-md-3"><small class="text-muted d-block">Time</small><strong>{{ $r->scheduled_at->format('h:i A') }}</strong></div>
                            <div class="col-6 col-md-3"><small class="text-muted d-block">Room</small><strong>{{ $r->room_no }}</strong></div>
                            <div class="col-6 col-md-3"><small class="text-muted d-block">Doctor / technician</small><strong>{{ $r->conducted_by }}</strong></div>
                        </div>
                        <p class="small text-muted mt-3 mb-0">Please arrive 10 minutes early with this report and follow the preparation notes below.</p>
                    </div>
                </div>
                @break

            @case('completed')
                <div class="alert alert-dark">These tests were done on {{ $r->scheduled_at->format('D, d M Y') }}.</div>
                @break

            @default
                <div class="alert alert-secondary">This request was cancelled.</div>
        @endswitch
    </div>

    @include('patient.tests._report', ['rows' => $rows, 'total' => $r->total_amount, 'meta' => $meta, 'code' => $r->code, 'date' => $r->created_at->format('d M Y')])

    @if($r->patient_note)
        <p class="small mt-3"><strong>Your note:</strong> {{ $r->patient_note }}</p>
    @endif
</div>
@endsection