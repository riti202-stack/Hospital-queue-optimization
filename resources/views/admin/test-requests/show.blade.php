@extends('layouts.main')
@section('content')
@php
    use App\Models\TestRequest;
    [$label, $cls] = $r->statusBadge();
    $cap = TestRequest::SLOT_CAPACITY;
    $rows = $r->items->map(fn ($i) => ['name' => $i->test_name, 'room' => $i->labTest?->room_no, 'prep' => $i->labTest?->preparation, 'price' => $i->price])->all();
    $times = [];
    for ($h = TestRequest::OPEN_HOUR; $h < TestRequest::CLOSE_HOUR; $h++) { foreach (['00', '30'] as $m) { $times[] = sprintf('%02d:%s', $h, $m); } }
    $canBook = in_array($r->status, ['pending', 'accepted', 'scheduled']);
    $canSuggest = in_array($r->status, ['pending', 'accepted']);
@endphp
<style>
    .slot-chip { display:inline-block; min-width:92px; text-align:center; padding:6px 8px; border-radius:10px; font-size:13px; font-weight:600; margin:0 4px 6px 0; }
    .slot-free { background:#e6f4ef; color:#0f6e56; }
    .slot-busy { background:#fff3cd; color:#8a5a05; }
    .slot-full { background:#f8d7da; color:#842029; text-decoration:line-through; }
</style>

<div class="page-card p-4">
    <a href="{{ route('admin.test-requests.index') }}" class="small text-decoration-none"><i class="ti ti-arrow-left"></i> All test requests</a>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2 mb-4">
        <h3 class="mb-0">{{ $r->code }} · {{ $r->patient->name ?? 'Patient' }}</h3>
        <span class="badge {{ $cls }} fs-6">{{ $label }}</span>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="row g-4">
        {{-- ===== Report ===== --}}
        <div class="col-lg-7">
            @include('patient.tests._report', [
                'rows' => $rows, 'total' => $r->total_amount, 'code' => $r->code, 'date' => $r->created_at->format('d M Y'),
                'meta' => [
                    'Patient'       => $r->patient->name ?? '—',
                    'Email'         => $r->patient->email ?? '—',
                    'Prescribed by' => $r->doctor?->user?->name ?? 'Not specified',
                    'Requested'     => $r->preferred_at->format('D, d M Y · h:i A'),
                ],
            ])
            @if($r->patient_note)<p class="small mt-3"><strong>Patient note:</strong> {{ $r->patient_note }}</p>@endif
            @if($r->admin_message)<p class="small"><strong>Last message sent:</strong> {{ $r->admin_message }}</p>@endif
        </div>

        {{-- ===== Actions ===== --}}
        <div class="col-lg-5">
            {{-- Requested slot check --}}
            <div class="card mb-3">
                <div class="card-body">
                    <small class="text-muted d-block">
                        {{ $r->status === 'accepted' ? 'Time the patient accepted' : ($r->status === 'scheduled' ? 'Current booking' : 'Time the patient asked for') }}
                    </small>
                    <div class="fs-5 fw-bold">{{ $target->format('l, d M Y · h:i A') }}</div>
                    @if($targetFree)
                        <span class="badge bg-success mt-1">Free · {{ $targetUsed }}/{{ $cap }} booked</span>
                    @else
                        <span class="badge bg-danger mt-1">
                            {{ $targetSlot->isPast() ? 'Time has passed' : (! TestRequest::isOpen($targetSlot) ? 'Lab closed' : "Full · {$targetUsed}/{$cap} booked") }}
                        </span>
                        @if($nearest)
                            <div class="small mt-2">Nearest free slot: <strong>{{ $nearest->format('D, d M · h:i A') }}</strong></div>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Day load --}}
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="mb-2">Lab load on {{ $bookAt->format('D, d M') }} <small class="text-muted">(max {{ $cap }} per slot)</small></h6>
                    @foreach($daySlots as $s)
                        @php $c = $s['used'] >= $cap ? 'slot-full' : ($s['used'] > 0 ? 'slot-busy' : 'slot-free'); @endphp
                        <span class="slot-chip {{ $c }}">{{ $s['time']->format('h:i A') }} · {{ $s['used'] }}/{{ $cap }}</span>
                    @endforeach
                </div>
            </div>

            {{-- Book --}}
            @if($canBook)
                <div class="card mb-3 border-success">
                    <div class="card-body">
                        <h5 class="text-success"><i class="ti ti-calendar-check"></i> {{ $r->status === 'scheduled' ? 'Change booking' : 'Book test slot' }}</h5>
                        <form method="POST" action="{{ route('admin.test-requests.book', $r) }}">
                            @csrf
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small">Date</label>
                                    <input type="date" name="date" class="form-control" value="{{ old('date', $bookAt->format('Y-m-d')) }}" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small">Time</label>
                                    <select name="time" class="form-select" required>
                                        @foreach($times as $v)
                                            <option value="{{ $v }}" {{ old('time', $bookAt->format('H:i')) === $v ? 'selected' : '' }}>
                                                {{ \Carbon\Carbon::createFromFormat('H:i', $v)->format('h:i A') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small">Room no.</label>
                                    <input type="text" name="room_no" class="form-control" value="{{ old('room_no', $defaultRoom) }}" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small">Doctor / technician</label>
                                    <input type="text" name="conducted_by" class="form-control" value="{{ old('conducted_by', $r->conducted_by) }}" placeholder="e.g. Dr. Rahman (Pathology)" required>
                                </div>
                            </div>
                            <button class="btn btn-brand w-100 mt-3"><i class="ti ti-check"></i> Book and notify patient</button>
                        </form>
                    </div>
                </div>
            @endif

            {{-- Suggest --}}
            @if($canSuggest)
                <div class="card mb-3 border-warning">
                    <div class="card-body">
                        <h5 style="color:#b8860b;"><i class="ti ti-message-2"></i> Can't book? Suggest another time</h5>
                        <form method="POST" action="{{ route('admin.test-requests.suggest', $r) }}">
                            @csrf
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small">Suggested date</label>
                                    <input type="date" name="date" class="form-control" value="{{ old('date', ($nearest ?? $bookAt)->format('Y-m-d')) }}" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small">Suggested time</label>
                                    <select name="time" class="form-select" required>
                                        @foreach($times as $v)
                                            <option value="{{ $v }}" {{ old('time', ($nearest ?? $bookAt)->format('H:i')) === $v ? 'selected' : '' }}>
                                                {{ \Carbon\Carbon::createFromFormat('H:i', $v)->format('h:i A') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small">Message to patient</label>
                                    <textarea name="admin_message" rows="4" class="form-control" required>{{ old('admin_message', $apology) }}</textarea>
                                </div>
                            </div>
                            <button class="btn btn-warning w-100 mt-3"><i class="ti ti-send"></i> Send apology &amp; suggestion</button>
                        </form>
                    </div>
                </div>
            @endif

            <div class="d-flex gap-2">
                @if($r->status === 'scheduled')
                    <form method="POST" action="{{ route('admin.test-requests.complete', $r) }}" class="flex-fill">
                        @csrf <button class="btn btn-dark w-100"><i class="ti ti-checks"></i> Mark tests done</button>
                    </form>
                @endif
                @unless(in_array($r->status, ['completed', 'cancelled']))
                    <form method="POST" action="{{ route('admin.test-requests.cancel', $r) }}" class="flex-fill" onsubmit="return confirm('Cancel this request?')">
                        @csrf <button class="btn btn-outline-danger w-100">Cancel request</button>
                    </form>
                @endunless
            </div>
        </div>
    </div>
</div>
@endsection