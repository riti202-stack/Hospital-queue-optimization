@extends('layouts.main')
@section('content')
<style>
    .q-card { display:block; background:#fff; border:1px solid #dfe8ee; border-radius:16px; padding:22px;
              text-decoration:none; color:inherit; height:100%; transition:.15s; }
    .q-card:hover { border-color:#0f6e56; box-shadow:0 6px 18px rgba(0,0,0,.08); transform:translateY(-2px); }
    .q-num { font-size:28px; font-weight:700; color:#0f6e56; line-height:1; }
    .q-lbl { font-size:12px; color:#6c757d; }
</style>

<div class="page-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><i class="ti ti-building-hospital"></i> Live Queue — Departments</h3>
        <a href="{{ route('queue-entries.create') }}" class="btn btn-brand"><i class="ti ti-plus"></i> Check In Patient</a>
    </div>

    @if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif

    <div class="row g-3">
        @forelse($departments as $d)
            <div class="col-sm-6 col-lg-4">
                <a href="{{ route('queue-entries.department', $d) }}" class="q-card">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="mb-0">{{ $d->name }}</h5>
                        <i class="ti ti-chevron-right text-muted"></i>
                    </div>
                    <div class="d-flex gap-4">
                        <div><div class="q-num">{{ $d->waiting_count }}</div><div class="q-lbl">Waiting</div></div>
                        <div><div class="q-num">{{ $d->seeing_count }}</div><div class="q-lbl">With doctor</div></div>
                        <div><div class="q-num text-secondary">{{ $d->doctors_count }}</div><div class="q-lbl">Doctors</div></div>
                    </div>
                </a>
            </div>
        @empty
            <p class="text-muted">No departments yet.</p>
        @endforelse
    </div>
</div>
<script>setTimeout(() => location.reload(), 30000);</script>
@endsection