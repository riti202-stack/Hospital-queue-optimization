@extends('layouts.main')
@section('content')
@php
    $hospitalName  = 'Hospital Queue System';   // your hospital's name
    $hospitalPhone = '+880 1700-000000';         // your front desk number
    $firstName = explode(' ', auth()->user()->name)[0];

    $icon = function ($name) {
        $n = strtolower($name);
        $map = [
            'cardio' => 'heartbeat', 'dent' => 'dental', 'ortho' => 'bone',
            'pediatr' => 'baby-carriage', 'paediatr' => 'baby-carriage', 'child' => 'baby-carriage',
            'emerg' => 'ambulance', 'neuro' => 'brain', 'opth' => 'eye', 'ophth' => 'eye', 'eye' => 'eye',
            'onco' => 'ribbon-health', 'surg' => 'cut', 'gyn' => 'gender-female', 'obs' => 'gender-female',
            'ent' => 'ear', 'medicine' => 'stethoscope', 'general' => 'stethoscope',
        ];
        foreach ($map as $key => $i) { if (str_contains($n, $key)) return $i; }
        return 'building-hospital';
    };

    $services = [
        ['ambulance',      'Emergency care',         'Open 24 hours, 7 days a week. Emergency patients are always seen first, before anyone else in the queue.', 'tel:999', 'Call 999'],
        ['door-enter',     'Outdoor (OPD)',          'Walk in, check in at the desk or from your phone, and see a doctor the same day without a paper token.', route('patient.checkin'), 'Check in'],
        ['stethoscope',    'Specialist consultation','Book a specialist in any department and choose the doctor, date and time that suit you.', route('patient.departments'), 'Book now'],
        ['test-pipe',      'Lab tests',              'Blood, urine and other tests ordered during your visit, with results added to your care record.', null, null],
        ['radioactive',    'Imaging & radiology',    'X-ray, ultrasound and other scans arranged by your doctor in the same visit where possible.', null, null],
        ['pill',           'Pharmacy',               'Collect your prescribed medicines before you leave, with clear advice on how to take them.', null, null],
        ['truck-delivery', 'Ambulance service',      'Round-the-clock ambulance support for emergencies and transfers. Call the hospital to arrange.', 'tel:'.preg_replace('/[^0-9+]/', '', $hospitalPhone), 'Call us'],
        ['heart-rate-monitor', 'Health check-ups',   'Routine check-ups to catch problems early. Book with a medicine specialist to start.', route('patient.departments'), 'Book a check-up'],
    ];

    $why = [
        ['heart-handshake', 'Excellent services',
            'Good treatment starts with good care. Our teams combine clinical skill with a friendly, respectful service, so every patient feels listened to from the moment they arrive until they leave.'],
        ['user-heart', 'Qualified doctors',
            'Every doctor is a registered specialist with verified qualifications and an up-to-date license. You can see each doctor’s degrees, experience and specialties before you book.'],
        ['ambulance', 'Emergency department',
            'Our emergency department is open around the clock. Our queue system puts emergency patients first automatically, so no one in danger is ever kept waiting behind routine visits.'],
        ['clock-hour-4', 'Shorter, fairer waiting',
            'No more sitting for hours without knowing when you will be called. You get a queue number, a live position and an expected time — and a phone alert the moment it is your turn.'],
        ['building-hospital', 'Modern facilities',
            'Clean consultation rooms, on-site lab and imaging, a pharmacy and a fully digital patient record mean fewer trips, less paperwork and a smoother visit.'],
    ];
@endphp

<style>
    .ph-hero { background: linear-gradient(135deg, #0b3d31 0%, #0f6e56 60%, #1a8f72 100%); color:#fff; border-radius:24px; padding:44px; position:relative; overflow:hidden; }
    .ph-hero::after { content:""; position:absolute; right:-120px; top:-120px; width:380px; height:380px; border-radius:50%; background:rgba(255,255,255,.07); }
    .ph-hero h1 { font-size:40px; font-weight:800; }
    .ph-hero p.lead { color:#d9f1e8; max-width:640px; }
    .ph-hero .btn-light { color:#0f6e56; font-weight:700; }
    .ph-visit { background:#fff; color:#13293d; border-radius:18px; padding:22px; }

    .ph-strip { display:grid; grid-template-columns:repeat(4,1fr); border-radius:18px; overflow:hidden; margin-top:24px; }
    .ph-strip a { display:flex; align-items:center; gap:14px; padding:26px 22px; color:#fff; text-decoration:none; font-weight:700; font-size:20px; text-transform:uppercase; transition:.15s; }
    .ph-strip a:hover { filter:brightness(1.08); }
    .ph-strip i { font-size:44px; }
    @media (max-width: 991px) { .ph-strip { grid-template-columns:repeat(2,1fr); } }
    @media (max-width: 575px) { .ph-strip { grid-template-columns:1fr; } }

    .ph-section { margin-top:56px; }
    .ph-title { font-weight:800; font-size:30px; text-transform:uppercase; }
    .ph-title span:first-child { color:#c0392b; }
    .ph-title span:last-child { color:#0f6e56; }
    .ph-underline { width:180px; height:4px; background:#0f6e56; border-radius:2px; margin:6px 0 24px; }
    .ph-sub { color:#5b6b78; max-width:760px; }

    .ph-photo { border-radius:20px; overflow:hidden; height:100%; min-height:420px;
                background:linear-gradient(160deg,#e8f7f2 0%,#cdeee3 45%,#a9dfcf 100%); display:flex; align-items:center; justify-content:center; }
    .ph-photo img { width:100%; height:100%; object-fit:cover; }
    .ph-photo .ph-photo-icon { font-size:180px; color:#0f6e56; opacity:.85; }

    .ph-why details { background:#f6f8f9; border:1px solid #e3eaee; border-radius:14px; margin-bottom:12px; }
    .ph-why summary { list-style:none; display:flex; align-items:center; gap:14px; padding:16px 20px; cursor:pointer; font-weight:700; font-size:19px; color:#c0392b; }
    .ph-why summary::-webkit-details-marker { display:none; }
    .ph-why .why-icon { width:46px; height:46px; border-radius:50%; background:#0f6e56; color:#fff; display:flex; align-items:center; justify-content:center; font-size:24px; flex:none; }
    .ph-why .chev { margin-left:auto; transition:.2s; color:#c0392b; font-size:22px; }
    .ph-why details[open] .chev { transform:rotate(180deg); }
    .ph-why details p { padding:0 22px 20px 80px; margin:0; color:#5b6b78; line-height:1.8; text-align:justify; }

    .ph-service { background:#fff; border:1px solid #dfe8ee; border-radius:18px; padding:24px; height:100%; display:flex; flex-direction:column; gap:10px; transition:.15s; }
    .ph-service:hover { border-color:#0f6e56; box-shadow:0 8px 22px rgba(0,0,0,.07); }
    .ph-service .s-icon { width:56px; height:56px; border-radius:50%; background:#e6f4ef; color:#0f6e56; display:flex; align-items:center; justify-content:center; font-size:28px; }
    .ph-service h5 { margin:0; font-weight:700; color:#0b3d31; }
    .ph-service p { margin:0; color:#5b6b78; font-size:14px; }
    .ph-service a { margin-top:auto; font-weight:700; color:#0f6e56; text-decoration:none; }

    .ph-stat { background:#fff; border:1px solid #dfe8ee; border-radius:18px; padding:22px; text-align:center; height:100%; }
    .ph-stat b { display:block; font-size:34px; color:#0f6e56; line-height:1.1; }
    .ph-stat small { color:#5b6b78; }

    .ph-dep { display:flex; gap:14px; align-items:center; background:#fff; border:1px solid #dfe8ee; border-radius:16px; padding:16px 18px; text-decoration:none; color:inherit; transition:.15s; height:100%; }
    .ph-dep:hover { border-color:#0f6e56; box-shadow:0 8px 22px rgba(0,0,0,.08); transform:translateY(-2px); }
    .ph-dep .d-icon { width:48px; height:48px; border-radius:12px; background:#e6f4ef; color:#0f6e56; display:flex; align-items:center; justify-content:center; font-size:26px; flex:none; }

    .ph-step { text-align:center; padding:0 10px; }
    .ph-step .num { width:52px; height:52px; border-radius:50%; background:#0f6e56; color:#fff; font-weight:800; display:inline-flex; align-items:center; justify-content:center; font-size:22px; margin-bottom:12px; }
    .ph-tips li { margin-bottom:12px; display:flex; gap:10px; }
    .ph-tips i { color:#0f6e56; font-size:20px; flex:none; }
</style>

<div class="container py-4">

    {{-- ================= WELCOME ================= --}}
    <div class="ph-hero">
        <div class="row g-4 align-items-center position-relative" style="z-index:1;">
            <div class="col-lg-7">
                <div class="text-uppercase small fw-bold mb-2" style="letter-spacing:1px;color:#a8e3cf;">Welcome to {{ $hospitalName }}</div>
                <h1 class="mb-3">{{ $greeting }}, {{ $firstName }}.</h1>
                <p class="lead mb-4">
                    Caring for you and your family is what we do best. Book a specialist in a minute,
                    check in when you arrive, and follow your place in the queue from anywhere —
                    we will let you know the moment the doctor is ready for you.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('patient.departments') }}" class="btn btn-light btn-lg"><i class="ti ti-calendar-plus"></i> Book appointment</a>
                    <a href="{{ route('patient.checkin') }}" class="btn btn-outline-light btn-lg"><i class="ti ti-login"></i> Check in</a>
                    <a href="{{ route('patient.queue-status') }}" class="btn btn-outline-light btn-lg"><i class="ti ti-list-numbers"></i> My queue</a>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="ph-visit">
                    <div class="small text-uppercase fw-bold text-muted mb-2">Your visit</div>
                    @if($myQueue)
                        <div class="d-flex align-items-center gap-3">
                            <div class="text-center px-3 py-2" style="border:2px dashed #0f6e56;border-radius:12px;">
                                <small class="text-muted d-block">Queue no.</small>
                                <span class="fs-2 fw-bold" style="color:#0f6e56;">#{{ $myQueue->token_no }}</span>
                            </div>
                            <div>
                                <div class="fw-bold">{{ $myQueue->status === 'in_progress' ? "It's your turn — please go in now" : 'You are checked in' }}</div>
                                <div class="small text-muted">
                                    {{ $myQueue->doctor?->user?->name ?? 'First available doctor' }} · {{ $myQueue->department?->name }}
                                    @if($myQueue->doctor?->room_no) · Room {{ $myQueue->doctor->room_no }} @endif
                                </div>
                            </div>
                        </div>
                        <a href="{{ route('patient.queue-status') }}" class="btn btn-brand w-100 mt-3">See live position</a>
                    @elseif($nextAppointment)
                        @php $t = \Carbon\Carbon::parse($nextAppointment->scheduled_time); @endphp
                        <div class="fw-bold">{{ $nextAppointment->doctor?->user?->name ?? 'Doctor' }}</div>
                        <div class="small text-muted mb-2">{{ $nextAppointment->department?->name }}</div>
                        <div><i class="ti ti-calendar"></i> {{ $t->isToday() ? 'Today' : $t->format('D, d M') }} at {{ $t->format('h:i A') }}</div>
                        <a href="{{ route('patient.appointments') }}" class="btn {{ $t->isToday() ? 'btn-brand' : 'btn-outline-success' }} w-100 mt-3">
                            {{ $t->isToday() ? 'Check in now' : 'View my appointments' }}
                        </a>
                    @else
                        <div class="fw-bold mb-1">No upcoming visits</div>
                        <div class="small text-muted mb-3">Choose a department and book a time that suits you.</div>
                        <a href="{{ route('patient.departments') }}" class="btn btn-brand w-100">Find a doctor</a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ================= SERVICE STRIP ================= --}}
    <div class="ph-strip">
        <a href="tel:999" style="background:#d93a35;"><i class="ti ti-ambulance"></i> 24 hours service</a>
        <a href="{{ route('patient.departments') }}" style="background:#11a05b;"><i class="ti ti-calendar-time"></i> Online appointment</a>
        <a href="{{ route('patient.queue-status') }}" style="background:#0b7a4b;"><i class="ti ti-list-numbers"></i> Live queue</a>
        <a href="{{ route('patient.departments') }}" style="background:#5a9e2f;"><i class="ti ti-user-heart"></i> Your physicians</a>
    </div>

    {{-- ================= WHY CHOOSE US ================= --}}
    <div class="ph-section">
        <div class="row g-4 align-items-stretch">
            <div class="col-lg-6">
                <div class="ph-photo">
                    @if(file_exists(public_path('images/hospital.jpg')))
                        <img src="{{ asset('images/hospital.jpg') }}" alt="{{ $hospitalName }}">
                    @else
                        <i class="ti ti-building-hospital ph-photo-icon"></i>
                    @endif
                </div>
            </div>
            <div class="col-lg-6">
                <div class="ph-title"><span>Why</span> <span>choose us?</span></div>
                <div class="ph-underline"></div>
                <div class="ph-why">
                    @foreach($why as $i => [$ic, $title, $text])
                        <details {{ $i === 0 ? 'open' : '' }}>
                            <summary>
                                <span class="why-icon"><i class="ti ti-{{ $ic }}"></i></span>
                                {{ $title }}
                                <i class="ti ti-chevron-down chev"></i>
                            </summary>
                            <p>{{ $text }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ================= OUR SERVICES ================= --}}
    <div class="ph-section">
        <div class="ph-title"><span>Our</span> <span>services</span></div>
        <div class="ph-underline"></div>
        <p class="ph-sub mb-4">From an emergency at midnight to a routine check-up, everything you need is under one roof.</p>
        <div class="row g-3">
            @foreach($services as [$ic, $title, $text, $link, $label])
                <div class="col-sm-6 col-lg-3">
                    <div class="ph-service">
                        <div class="s-icon"><i class="ti ti-{{ $ic }}"></i></div>
                        <h5>{{ $title }}</h5>
                        <p>{{ $text }}</p>
                        @if($link)<a href="{{ $link }}">{{ $label }} →</a>@endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ================= LIVE NUMBERS ================= --}}
    <div class="ph-section">
        <div class="ph-title"><span>Today</span> <span>at a glance</span></div>
        <div class="ph-underline"></div>
        <div class="row g-3">
            <div class="col-6 col-lg"><div class="ph-stat"><b>{{ $stats['departments'] }}</b><small>Departments</small></div></div>
            <div class="col-6 col-lg"><div class="ph-stat"><b>{{ $stats['doctors'] }}</b><small>Specialist doctors</small></div></div>
            <div class="col-6 col-lg"><div class="ph-stat"><b>{{ $stats['onDuty'] }}</b><small>Doctors on duty today</small></div></div>
            <div class="col-6 col-lg"><div class="ph-stat"><b>{{ $stats['avgWait'] !== null ? $stats['avgWait'].' min' : '—' }}</b><small>Average wait today</small></div></div>
            <div class="col-12 col-lg"><div class="ph-stat"><b>{{ $stats['served'] }}</b><small>Patients served this month</small></div></div>
        </div>
    </div>

    {{-- ================= DEPARTMENTS ================= --}}
    <div class="ph-section">
        <div class="ph-title"><span>Our</span> <span>departments</span></div>
        <div class="ph-underline"></div>
        <div class="row g-3">
            @foreach($departments->where('doctor_count', '>', 0) as $d)
                <div class="col-sm-6 col-lg-4">
                    <a href="{{ route('patient.department.doctors', $d) }}" class="ph-dep">
                        <div class="d-icon"><i class="ti ti-{{ $icon($d->name) }}"></i></div>
                        <div>
                            <div class="fw-bold">{{ ucfirst($d->name) }}</div>
                            <small class="text-muted">{{ $d->doctor_count }} {{ $d->doctor_count === 1 ? 'doctor' : 'doctors' }} · {{ $d->available_count }} on duty</small>
                        </div>
                        <i class="ti ti-chevron-right ms-auto text-muted"></i>
                    </a>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ================= HOW IT WORKS ================= --}}
    <div class="ph-section">
        <div class="ph-title"><span>How</span> <span>it works</span></div>
        <div class="ph-underline"></div>
        <div class="row g-4">
            <div class="col-6 col-lg-3 ph-step"><div class="num">1</div><h6 class="fw-bold">Book</h6><p class="small text-muted mb-0">Pick a department, a doctor and a free time slot.</p></div>
            <div class="col-6 col-lg-3 ph-step"><div class="num">2</div><h6 class="fw-bold">Check in</h6><p class="small text-muted mb-0">On the day, check in from your phone and get your queue number.</p></div>
            <div class="col-6 col-lg-3 ph-step"><div class="num">3</div><h6 class="fw-bold">Wait anywhere</h6><p class="small text-muted mb-0">Follow your live position and expected time from anywhere nearby.</p></div>
            <div class="col-6 col-lg-3 ph-step"><div class="num">4</div><h6 class="fw-bold">Get called</h6><p class="small text-muted mb-0">Your phone alerts you when it is your turn, with the room number.</p></div>
        </div>
    </div>

    {{-- ================= BEFORE YOUR VISIT ================= --}}
    <div class="ph-section mb-4">
        <div class="ph-title"><span>Before</span> <span>your visit</span></div>
        <div class="ph-underline"></div>
        <ul class="list-unstyled ph-tips mb-0">
            <li><i class="ti ti-file-text"></i> Bring your previous prescriptions and test reports so your doctor can see your history.</li>
            <li><i class="ti ti-pill"></i> Make a list of the medicines you take, including the dose.</li>
            <li><i class="ti ti-clock"></i> Arrive about 10 minutes before your appointment and check in as soon as you reach the hospital.</li>
            <li><i class="ti ti-bell-ringing"></i> Keep this page open on your phone so you don't miss the “It's your turn” alert.</li>
            <li><i class="ti ti-alert-triangle"></i> In an emergency, don't wait for an appointment — come straight to the Emergency department or call 999.</li>
        </ul>
    </div>
</div>
@endsection