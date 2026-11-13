@extends('layouts.main')

@section('content')
<div class="page-card">
    <a href="{{ url()->previous() }}" class="btn btn-link px-0 mb-2"><i class="ti ti-arrow-left"></i> Back</a>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h3 class="mb-0"><i class="ti ti-history"></i> Patient history</h3>
            <div class="text-muted">
                <strong>{{ $dependent ? $dependent->label() : $patient->name }}</strong>
                @if($dependent)
                    · Account holder: {{ $patient->name }}
                    @if($dependent->age() !== null) · Age {{ $dependent->age() }} @endif
                    @if($dependent->gender) · {{ $dependent->gender }} @endif
                    @if($dependent->blood_group) · Blood {{ $dependent->blood_group }} @endif
                @else
                    · {{ $patient->phone ?? '' }}
                @endif
            </div>
        </div>
        <span class="badge bg-success fs-6">{{ $visits }} completed visit(s)</span>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <h5>Prescriptions</h5>
            @forelse($prescriptions as $p)
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between">
                        <span><strong>{{ $p->created_at->format('d M Y') }}</strong> · {{ $p->doctor?->displayName() }}</span>
                        <span class="text-muted small">{{ $p->code() }}</span>
                    </div>
                    <div class="card-body small">
                        @if($p->symptoms)<p class="mb-1"><span class="text-muted">Symptoms:</span> {{ $p->symptoms }}</p>@endif
                        <p class="mb-2"><span class="text-muted">Diagnosis:</span> <strong>{{ $p->diagnosis }}</strong></p>
                        @if($p->items->isNotEmpty())
                            <table class="table table-sm mb-2">
                                <thead class="table-light"><tr><th>Medicine</th><th>Dosage</th><th>Duration</th><th>Instruction</th></tr></thead>
                                <tbody>
                                    @foreach($p->items as $it)
                                        <tr><td>{{ $it->medicine }}</td><td>{{ $it->dosage }}</td><td>{{ $it->duration }}</td><td>{{ $it->instruction ?? '—' }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                        @if($p->tests_advised)<p class="mb-1"><span class="text-muted">Tests:</span> {{ $p->tests_advised }}</p>@endif
                        @if($p->advice)<p class="mb-1"><span class="text-muted">Advice:</span> {{ $p->advice }}</p>@endif
                        @if($p->follow_up_date)<p class="mb-0"><span class="text-muted">Follow-up:</span> {{ $p->follow_up_date->format('d M Y') }}</p>@endif
                    </div>
                </div>
            @empty
                <p class="text-muted">No prescriptions yet.</p>
            @endforelse
            {{ $prescriptions->links() }}
        </div>

        <div class="col-lg-4">
            <h5>Lab reports</h5>
            @forelse($labReports as $r)
                <div class="d-flex justify-content-between align-items-center border rounded p-2 mb-2 bg-white">
                    <div class="small">
                        <strong>LT-{{ str_pad($r->id, 5, '0', STR_PAD_LEFT) }}</strong><br>
                        <span class="text-muted">{{ \Carbon\Carbon::parse($r->report_uploaded_at)->format('d M Y') }}</span>
                    </div>
                    <a href="{{ route('lab-reports.download', $r) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-download"></i></a>
                </div>
            @empty
                <p class="text-muted small">No lab reports.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection