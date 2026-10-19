@extends('layouts.main')
@section('content')
<style>
    .doc-card { background:#fff; border:1px solid #dfe8ee; border-radius:18px; padding:24px; height:100%;
                display:flex; flex-direction:column; gap:14px; }
    .doc-card.off { opacity:.6; }
    .doc-photo { width:96px; height:96px; border-radius:50%; object-fit:cover; flex:none; }
    .doc-avatar { width:96px; height:96px; border-radius:50%; background:#0f6e56; color:#fff; font-size:38px; font-weight:700;
                  display:flex; align-items:center; justify-content:center; flex:none; }
    .info-row { display:flex; gap:10px; font-size:14px; color:#3f5468; }
    .info-row i { color:#0f6e56; font-size:18px; flex:none; }
    .stat { background:#f1faf7; border-radius:12px; padding:8px 14px; text-align:center; }
    .stat b { display:block; font-size:20px; color:#0f6e56; }
    .stat small { font-size:11px; color:#6c757d; }
</style>

<div class="page-card p-4">
    <a href="{{ route('patient.departments') }}" class="small text-decoration-none"><i class="ti ti-arrow-left"></i> All departments</a>
    <h3 class="mt-2 mb-1">{{ ucfirst($department->name) }} doctors</h3>
    <p class="text-muted mb-4">Step 2 of 3 · Choose a doctor</p>

    <div class="row g-3">
        @forelse($doctors as $doc)
            @php $name = $doc->user->name ?? 'Doctor'; @endphp
            <div class="col-lg-6">
                <div class="doc-card {{ $doc->is_available ? '' : 'off' }}">
                    <div class="d-flex gap-3 align-items-center">
                        @if($doc->photo)
                            <img src="{{ asset('storage/'.$doc->photo) }}" alt="{{ $name }}" class="doc-photo">
                        @else
                            <div class="doc-avatar">{{ strtoupper(substr(preg_replace('/^Dr\.?\s*/i', '', $name), 0, 1)) }}</div>
                        @endif
                        <div class="flex-grow-1">
                            <h5 class="mb-0">{{ $name }}</h5>
                            <div class="fw-semibold" style="color:#0f6e56;">{{ $doc->specialization ?? ucfirst($department->name) }}</div>
                            @if($doc->sub_specialty)<div class="small text-muted">{{ $doc->sub_specialty }}</div>@endif
                            @if($doc->degrees)<div class="small mt-1">{{ $doc->degrees }}</div>@endif
                        </div>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        @if($doc->experience_years)
                            <div class="stat"><b>{{ $doc->experience_years }}+</b><small>Years experience</small></div>
                        @endif
                        <div class="stat"><b>{{ $doc->waiting_now }}</b><small>Waiting now</small></div>
                        <div class="stat"><b>{{ $doc->room_no ?? '—' }}</b><small>Room</small></div>
                    </div>

                    @if($doc->about)
                        <p class="mb-0" style="font-size:14px;">{{ $doc->about }}</p>
                    @endif

                    <div class="d-flex flex-column gap-2">
                        <div class="info-row"><i class="ti ti-clock"></i>
                            <span><b>Visiting hours:</b> {{ $doc->working_hours ?? 'Please contact the hospital' }}</span></div>
                        <div class="info-row"><i class="ti ti-phone"></i>
                            <span><b>Appointment line:</b> {{ $doc->chamber_phone ?? 'Hospital reception' }}</span></div>
                    </div>

                    <div class="mt-auto">
                        @if($doc->is_available)
                            <a href="{{ route('patient.doctor.book', $doc) }}" class="btn btn-brand w-100">
                                <i class="ti ti-calendar-plus"></i> Book with {{ $name }}
                            </a>
                        @else
                            <button class="btn btn-secondary w-100" disabled>Not taking bookings right now</button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <p class="text-muted">No doctors in this department yet.</p>
        @endforelse
    </div>
</div>
@endsection