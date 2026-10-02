@extends('layouts.main')
@section('content')
<div class="page-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="ti ti-calendar"></i> Appointments</h3>
        <a href="{{ route('appointments.create') }}" class="btn btn-brand"><i class="ti ti-plus"></i> Book Appointment</a>
    </div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="row g-3">
        @forelse($appointments as $a)
            <div class="col-md-6 col-lg-4">
                <div class="card entry-card p-3 h-100">
                    <div class="d-flex justify-content-between">
                        <h6 class="mb-1">{{ $a->patient->name }}</h6>
                        @php $bc = match($a->noShowRisk ?? 'unknown'){'high'=>'danger','medium'=>'warning','low'=>'success',default=>'secondary'}; @endphp
                        <span class="badge bg-{{ $bc }}">{{ ucfirst($a->noShowRisk ?? 'unknown') }} risk</span>
                    </div>
                    <p class="text-muted small mb-1"><i class="ti ti-stethoscope"></i> {{ $a->doctor->user->name }} — {{ $a->department->name }}</p>
                    <p class="text-muted small mb-2"><i class="ti ti-clock"></i> {{ $a->scheduled_time }}</p>
                    <span class="badge bg-dark mb-2">{{ ucfirst($a->status) }}</span>
                    <div class="mt-auto d-flex gap-2">
                        <a href="{{ route('appointments.edit', $a) }}" class="btn btn-sm btn-outline-secondary flex-fill">Edit</a>
                        <form action="{{ route('appointments.destroy', $a) }}" method="POST" class="flex-fill">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger w-100" onclick="return confirm('Delete?')">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-muted">No appointments yet.</p>
        @endforelse
    </div>
    <div class="mt-3">{{ $appointments->links() }}</div>
</div>
@endsection