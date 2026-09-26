@extends('layouts.main')
@section('content')
<div class="d-flex align-items-center justify-content-center" style="min-height: 75vh;">
    <div class="page-card text-center" style="max-width: 640px;">
        <i class="ti ti-building-hospital" style="font-size: 56px; color: var(--brand);"></i>
        <h1 class="fw-bold mt-3 mb-2">Hospital Queue Optimization</h1>
        <p class="text-muted mb-4">
            Fair, priority-based patient scheduling across departments —
            combining walk-ins and appointments into one live, transparent queue.
        </p>

        @guest
            <div class="d-flex justify-content-center gap-3">
                <a href="{{ route('login') }}" class="btn btn-brand px-4 py-2"><i class="ti ti-login"></i> Log in</a>
                <a href="{{ route('register') }}" class="btn btn-outline-secondary px-4 py-2"><i class="ti ti-user-plus"></i> Register</a>
            </div>
        @else
            <a href="{{ route('dashboard') }}" class="btn btn-brand px-4 py-2"><i class="ti ti-layout-dashboard"></i> Go to Dashboard</a>
        @endguest

        <hr class="my-4">

        <div class="row text-start g-3">
            <div class="col-md-4">
                <i class="ti ti-priority" style="color: var(--brand);"></i>
                <p class="small mb-0 mt-1"><strong>Priority + aging</strong><br>Urgent cases seen first, without routine patients waiting forever.</p>
            </div>
            <div class="col-md-4">
                <i class="ti ti-clock" style="color: var(--brand);"></i>
                <p class="small mb-0 mt-1"><strong>Live wait times</strong><br>Real-time queue position, no guessing.</p>
            </div>
            <div class="col-md-4">
                <i class="ti ti-shield-check" style="color: var(--brand);"></i>
                <p class="small mb-0 mt-1"><strong>Crash-safe</strong><br>No patient data lost, even mid-visit.</p>
            </div>
        </div>
    </div>
</div>
@endsection