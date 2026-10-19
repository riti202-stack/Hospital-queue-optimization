@extends('layouts.main')
@section('content')
<style>
    .role-card { display:flex; flex-direction:column; gap:10px; background:#fff; border:1px solid #dfe8ee; border-radius:18px;
                 padding:28px; height:100%; text-decoration:none; color:inherit; transition:.15s; }
    .role-card:hover { border-color:#0f6e56; box-shadow:0 8px 22px rgba(0,0,0,.08); transform:translateY(-2px); }
    .role-icon { width:56px; height:56px; border-radius:14px; background:#e6f4ef; color:#0f6e56;
                 display:flex; align-items:center; justify-content:center; font-size:30px; }
    .role-num { font-size:40px; font-weight:800; color:#0f6e56; line-height:1; }
</style>

<div class="page-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0"><i class="ti ti-users"></i> Users</h3>
        <a href="{{ route('users.create') }}" class="btn btn-brand"><i class="ti ti-plus"></i> Add User</a>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    @include('users._tabs', ['tab' => 'overview'])

    <div class="row g-3">
        <div class="col-md-4">
            <a href="{{ route('users.patients') }}" class="role-card">
                <div class="role-icon"><i class="ti ti-user-heart"></i></div>
                <div class="role-num">{{ $counts['patient'] ?? 0 }}</div>
                <h5 class="mb-0">Patients</h5>
                <small class="text-muted">{{ $newPatientsThisWeek }} registered in the last 7 days</small>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('users.doctors') }}" class="role-card">
                <div class="role-icon"><i class="ti ti-stethoscope"></i></div>
                <div class="role-num">{{ $counts['doctor'] ?? 0 }}</div>
                <h5 class="mb-0">Doctors</h5>
                <small class="text-muted">
                    Grouped by department
                    @if($doctorsWithoutProfile) · <span class="text-danger">{{ $doctorsWithoutProfile }} without a profile</span>@endif
                </small>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('users.admins') }}" class="role-card">
                <div class="role-icon"><i class="ti ti-shield-lock"></i></div>
                <div class="role-num">{{ $counts['admin'] ?? 0 }}</div>
                <h5 class="mb-0">Admins</h5>
                <small class="text-muted">Hospital administrators</small>
            </a>
        </div>
    </div>
</div>
@endsection