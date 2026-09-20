@extends('layouts.app')
@section('content')
<div class="container">
    <h2>Update Queue Entry</h2>
    <form action="{{ route('queue-entries.update', $queueEntry) }}" method="POST">
        @csrf @method('PUT')
        <div class="mb-3">
            <label>Doctor</label>
            <select name="doctor_id" class="form-control">
                @foreach($doctors as $d)
                    <option value="{{ $d->id }}" {{ $queueEntry->doctor_id == $d->id ? 'selected' : '' }}>{{ $d->user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label>Status</label>
            <select name="status" class="form-control">
                @foreach(['waiting','in_progress','completed'] as $s)
                    <option value="{{ $s }}" {{ $queueEntry->status == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-primary">Update</button>
    </form>
</div>
@endsection