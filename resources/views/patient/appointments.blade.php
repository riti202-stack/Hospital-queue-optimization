@extends('layouts.main')
@section('content')
<div class="page-card">
    <h3 class="mb-4"><i class="ti ti-calendar"></i> My appointments</h3>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="row g-3">
        @forelse($appointments as $a)
            <div class="col-md-6">
                <div class="card entry-card p-3">
                    <h5>{{ $a->doctor->user->name }}</h5>
                    <p class="text-muted mb-1">{{ $a->department->name }}</p>
                    <p class="mb-1"><i class="ti ti-clock"></i> {{ $a->scheduled_time }}</p>
                    <span class="badge bg-secondary">{{ ucfirst($a->status) }}</span>
                </div>
            </div>
        @empty
            <p class="text-muted">You have no appointments yet.</p>
        @endforelse
    </div>
    {{ $appointments->links() }}
</div>
@endsection