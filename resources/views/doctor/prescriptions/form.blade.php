@extends('layouts.main')

@section('content')
@php
    $items = old('items', $rx->exists
        ? $rx->items->map->only(['medicine', 'dosage', 'duration', 'instruction'])->all()
        : [[], [], []]);
    if (empty($items)) $items = [[]];
@endphp

<div class="page-card">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h3 class="mb-0"><i class="ti ti-prescription"></i> {{ $rx->exists ? 'Edit prescription '.$rx->code() : 'Write prescription' }}</h3>
            <small class="text-muted">Patient: <strong>{{ $patientName }}</strong> · Token {{ $token }}</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('doctor.patients.history', ['patient' => $patientId, 'dependent' => $dependentId]) }}" class="btn btn-outline-secondary btn-sm">
                <i class="ti ti-history"></i> Full history
            </a>
            @if($rx->exists)
                <a href="{{ route('doctor.prescriptions.pdf', $rx) }}" class="btn btn-outline-primary btn-sm"><i class="ti ti-download"></i> PDF</a>
            @endif
            <a href="{{ route('doctor.queue') }}" class="btn btn-link btn-sm">Back to queue</a>
        </div>
    </div>

    @include('partials.flash')

    <div class="row g-4">
        <div class="col-lg-8">
            <form method="POST" action="{{ $action }}">
                @csrf
                @if($rx->exists) @method('PUT') @endif

                <div class="mb-3">
                    <label class="form-label">Symptoms / chief complaints</label>
                    <textarea name="symptoms" rows="2" class="form-control" placeholder="e.g. Fever for 3 days, headache">{{ old('symptoms', $rx->symptoms) }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Diagnosis <span class="text-danger">*</span></label>
                    <textarea name="diagnosis" rows="2" class="form-control @error('diagnosis') is-invalid @enderror" required>{{ old('diagnosis', $rx->diagnosis) }}</textarea>
                    @error('diagnosis')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <label class="form-label fw-semibold">Medicines</label>
                <div class="table-responsive">
                    <table class="table table-sm align-middle" id="rx-items">
                        <thead class="table-light">
                            <tr><th style="width:35%">Medicine</th><th>Dosage</th><th>Duration</th><th>Instruction</th><th></th></tr>
                        </thead>
                        <tbody>
                            @foreach($items as $i => $it)
                                <tr>
                                    <td><input name="items[{{ $i }}][medicine]" value="{{ $it['medicine'] ?? '' }}" class="form-control form-control-sm" list="medicine-list" placeholder="Napa 500mg"></td>
                                    <td><input name="items[{{ $i }}][dosage]" value="{{ $it['dosage'] ?? '' }}" class="form-control form-control-sm" list="dosage-list" placeholder="1+0+1"></td>
                                    <td><input name="items[{{ $i }}][duration]" value="{{ $it['duration'] ?? '' }}" class="form-control form-control-sm" placeholder="7 days"></td>
                                    <td><input name="items[{{ $i }}][instruction]" value="{{ $it['instruction'] ?? '' }}" class="form-control form-control-sm" list="instruction-list" placeholder="After meal"></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="ti ti-x"></i></button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <button type="button" id="add-row" class="btn btn-sm btn-outline-primary mb-3"><i class="ti ti-plus"></i> Add medicine</button>

                <datalist id="medicine-list">
                    @foreach(['Napa 500mg','Napa Extra','Paracetamol 500mg','Seclo 20mg','Omeprazole 20mg','Pantoprazole 40mg','Amoxicillin 500mg','Azithromycin 500mg','Cefixime 200mg','Cetirizine 10mg','Fexofenadine 120mg','Montelukast 10mg','Metformin 500mg','Amlodipine 5mg','Losartan 50mg','Atorvastatin 10mg','Salbutamol inhaler','ORS'] as $m)
                        <option value="{{ $m }}">
                    @endforeach
                </datalist>
                <datalist id="dosage-list">
                    @foreach(['1+0+1','1+1+1','0+0+1','1+0+0','0+1+0','1+1+1+1','As needed'] as $d)<option value="{{ $d }}">@endforeach
                </datalist>
                <datalist id="instruction-list">
                    @foreach(['After meal','Before meal','Empty stomach','At bedtime','When fever > 100°F'] as $d)<option value="{{ $d }}">@endforeach
                </datalist>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Tests advised</label>
                        <textarea name="tests_advised" rows="2" class="form-control" placeholder="CBC, Urine R/E">{{ old('tests_advised', $rx->tests_advised) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Advice</label>
                        <textarea name="advice" rows="2" class="form-control" placeholder="Drink plenty of water, rest">{{ old('advice', $rx->advice) }}</textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Follow-up date</label>
                        <input type="date" name="follow_up_date" class="form-control" min="{{ today()->addDay()->toDateString() }}"
                               value="{{ old('follow_up_date', optional($rx->follow_up_date)->toDateString()) }}">
                    </div>
                </div>

                <button class="btn btn-brand mt-3"><i class="ti ti-device-floppy"></i> {{ $rx->exists ? 'Update prescription' : 'Save & send to patient' }}</button>
            </form>
        </div>

        <div class="col-lg-4">
            <h6 class="fw-semibold">Previous prescriptions</h6>
            @forelse($past as $p)
                <div class="card mb-2">
                    <div class="card-body p-3 small">
                        <div class="d-flex justify-content-between">
                            <strong>{{ $p->created_at->format('d M Y') }}</strong>
                            <span class="text-muted">{{ $p->doctor?->displayName() }}</span>
                        </div>
                        <div class="mt-1"><span class="text-muted">Dx:</span> {{ $p->diagnosis }}</div>
                        @if($p->items->isNotEmpty())
                            <ul class="mb-0 ps-3 mt-1">
                                @foreach($p->items as $it)<li>{{ $it->medicine }} — {{ $it->dosage }} × {{ $it->duration }}</li>@endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-muted small">No earlier prescriptions.</p>
            @endforelse
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tbody = document.querySelector('#rx-items tbody');
    let idx = {{ count($items) }};

    document.getElementById('add-row').addEventListener('click', function () {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input name="items[${idx}][medicine]" class="form-control form-control-sm" list="medicine-list" placeholder="Medicine"></td>
            <td><input name="items[${idx}][dosage]" class="form-control form-control-sm" list="dosage-list" placeholder="1+0+1"></td>
            <td><input name="items[${idx}][duration]" class="form-control form-control-sm" placeholder="7 days"></td>
            <td><input name="items[${idx}][instruction]" class="form-control form-control-sm" list="instruction-list" placeholder="After meal"></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="ti ti-x"></i></button></td>`;
        tbody.appendChild(tr);
        idx++;
    });

    tbody.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-row');
        if (btn && tbody.rows.length > 1) btn.closest('tr').remove();
    });
});
</script>
@endsection