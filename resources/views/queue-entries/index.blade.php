@extends('layouts.main')
@section('content')
<div class="page-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="ti ti-list-details"></i> Live Queue (All Departments)</h3>
        <a href="{{ route('queue-entries.create') }}" class="btn btn-brand"><i class="ti ti-plus"></i> Check In Patient</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="row g-3">
        @forelse($entries as $e)
            <div class="col-md-6 col-lg-4">
                <div class="card entry-card p-3 h-100">
                    <div class="d-flex justify-content-between mb-1">
                        <h6 class="mb-0">{{ $e->patient->name }}</h6>
                        @php $bc = match($e->urgency_level){'emergency'=>'badge-emergency','urgent'=>'badge-urgent',default=>'badge-routine'}; @endphp
                        <span class="badge {{ $bc }}">{{ ucfirst($e->urgency_level) }}</span>
                    </div>
                    <p class="text-muted small mb-1"><i class="ti ti-stethoscope"></i> {{ $e->doctor->user->name ?? 'Not assigned' }}</p>
                    <p class="text-muted small mb-1"><i class="ti ti-chart-bar"></i> Priority: {{ number_format($e->priority_score, 1) }}</p>
                    <span class="badge bg-dark mb-2">{{ ucfirst(str_replace('_',' ',$e->status)) }}</span>
                    <div class="mt-auto d-flex gap-2">
                        <a href="{{ route('queue-entries.edit', $e) }}" class="btn btn-sm btn-outline-secondary flex-fill">Edit</a>
                        <form action="{{ route('queue-entries.destroy', $e) }}" method="POST" class="flex-fill">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger w-100" onclick="return confirm('Delete?')">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-muted">No patients waiting.</p>
        @endforelse
    </div>
    <div class="mt-3">{{ $entries->links() }}</div>
</div>
@endsection