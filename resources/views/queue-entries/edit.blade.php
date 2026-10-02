@extends('layouts.main')
@section('content')
<div class="page-card p-4" style="max-width:600px;">
    <h3 class="mb-4"><i class="ti ti-edit"></i> Update Queue Entry</h3>
    <form action="{{ route('queue-entries.update', $queueEntry) }}" method="POST">
        @csrf @method('PUT')
        <div class="mb-3">
            <label class="form-label">Doctor</label>
            <select name="doctor_id" class="form-select">
                @foreach($doctors as $d)<option value="{{ $d->id }}" {{ $queueEntry->doctor_id == $d->id ? 'selected' : '' }}>{{ $d->user->name }}</option>@endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                @foreach(['waiting','in_progress','completed'] as $s)
                    <option value="{{ $s }}" {{ $queueEntry->status == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-brand"><i class="ti ti-check"></i> Update</button>
    </form>
</div>
@endsection