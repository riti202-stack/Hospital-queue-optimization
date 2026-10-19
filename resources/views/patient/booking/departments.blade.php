@extends('layouts.main')
@section('content')
@php
    $icon = function ($name) {
        $n = strtolower($name);
        $map = [
            'cardio' => 'heartbeat', 'dent' => 'dental', 'ortho' => 'bone',
            'pediatr' => 'baby-carriage', 'paediatr' => 'baby-carriage', 'child' => 'baby-carriage',
            'emerg' => 'ambulance', 'neuro' => 'brain', 'opth' => 'eye', 'ophth' => 'eye', 'eye' => 'eye',
            'onco' => 'ribbon-health', 'surg' => 'cut', 'gyn' => 'gender-female', 'obs' => 'gender-female',
            'ent' => 'ear', 'medicine' => 'stethoscope', 'general' => 'stethoscope',
        ];
        foreach ($map as $key => $i) {
            if (str_contains($n, $key)) return $i;
        }
        return 'building-hospital';
    };
@endphp

<style>
    .dep-card { display:flex; flex-direction:column; gap:12px; background:#fff; border:1px solid #dfe8ee; border-radius:18px;
                padding:24px; height:100%; text-decoration:none; color:inherit; transition:.15s; }
    .dep-card:hover { border-color:#0f6e56; box-shadow:0 8px 22px rgba(0,0,0,.08); transform:translateY(-2px); }
    .dep-card.disabled { opacity:.55; pointer-events:none; }
    .dep-icon { width:56px; height:56px; border-radius:14px; background:#e6f4ef; color:#0f6e56;
                display:flex; align-items:center; justify-content:center; font-size:30px; flex:none; }
    .chip { display:inline-block; background:#f1f5f8; border-radius:999px; padding:3px 10px; font-size:12px; margin:0 4px 4px 0; color:#3f5468; }
</style>

<div class="page-card p-4">
    <h3 class="mb-1"><i class="ti ti-calendar-plus"></i> Book an appointment</h3>
    <p class="text-muted mb-4">Step 1 of 3 · Choose a department</p>

    <div class="row g-3">
        @foreach($departments as $d)
            <div class="col-sm-6 col-lg-4">
                <a href="{{ route('patient.department.doctors', $d) }}" class="dep-card {{ $d->doctor_count ? '' : 'disabled' }}">
                    <div class="d-flex align-items-center gap-3">
                        <div class="dep-icon"><i class="ti ti-{{ $icon($d->name) }}"></i></div>
                        <div>
                            <h5 class="mb-0">{{ ucfirst($d->name) }}</h5>
                            <small class="text-muted">
                                {{ $d->doctor_count }} {{ $d->doctor_count === 1 ? 'doctor' : 'doctors' }} · {{ $d->available_count }} available
                            </small>
                        </div>
                    </div>
                    @if($d->services->isNotEmpty())
                        <div>@foreach($d->services as $s)<span class="chip">{{ $s }}</span>@endforeach</div>
                    @endif
                    <div class="mt-auto small fw-semibold" style="color:#0f6e56;">
                        {{ $d->doctor_count ? 'See doctors →' : 'No doctors yet' }}
                    </div>
                </a>
            </div>
        @endforeach
    </div>
</div>
@endsection